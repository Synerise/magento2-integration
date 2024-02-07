<?php

namespace Synerise\Integration\SyneriseApi\Sender\Data;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Model\Customer as CustomerModel;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Store\Model\ScopeInterface;
use Synerise\ApiClient\Api\DefaultApi;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\ApiClient\Model\InBodyClientSex;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking\UuidManagement;
use Synerise\Integration\Model\Config\Source\Debug\Exclude;
use Synerise\Integration\Model\Workspace\ConfigFactory as WorkspaceConfigFactory;
use Synerise\Integration\SyneriseApi\Sender\AbstractSender;
use Synerise\Integration\Model\Config\Source\Customers\Attributes;
use Synerise\Integration\SyneriseApi\ConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory;

class Customer extends AbstractSender implements SenderInterface
{
    public const MODEL = 'customer';

    public const ENTITY_ID = 'entity_id';

    public const MAPPING_GENDER = [
        1 => InBodyClientSex::MALE,
        2 => InBodyClientSex::FEMALE,
        3 => InBodyClientSex::NOT_SPECIFIED
    ];

    /**
     * @var AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @param AddressRepositoryInterface $addressRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param ResourceConnection $resource
     * @param Logger $loggerHelper
     * @param ConfigFactory $configFactory
     * @param InstanceFactory $apiInstanceFactory
     * @param WorkspaceConfigFactory $workspaceConfigFactory
     */
    public function __construct(
        AddressRepositoryInterface $addressRepository,
        ScopeConfigInterface $scopeConfig,
        ResourceConnection $resource,
        Logger $loggerHelper,
        ConfigFactory $configFactory,
        InstanceFactory $apiInstanceFactory,
        WorkspaceConfigFactory $workspaceConfigFactory
    ) {
        $this->addressRepository = $addressRepository;
        $this->scopeConfig = $scopeConfig;
        $this->resource = $resource;

        parent::__construct($loggerHelper, $configFactory, $apiInstanceFactory, $workspaceConfigFactory);
    }

    /**
     * Send Items
     *
     * @param Collection|CustomerModel[] $collection
     * @param int $storeId
     * @param int|null $websiteId
     * @return void
     * @throws ApiException|ValidatorException
     */
    public function sendItems($collection, int $storeId, ?int $websiteId = null)
    {
        {
            $createAClientInCrmRequests = [];
            $ids = [];

        foreach ($collection as $customer) {
            $createAClientInCrmRequests[] = new CreateaClientinCRMRequest($this->prepareParams($customer, $storeId));
            $ids[] = $customer->getEntityId();
        }

        if (!empty($createAClientInCrmRequests)) {
            $this->batchAddOrUpdateClients(
                $createAClientInCrmRequests,
                $storeId
            );
            $this->markCustomersAsSent($ids, $storeId);
        }

        }
    }

    /**
     * Batch add or update clients
     *
     * @param mixed $payload
     * @param int $storeId
     * @param string|null $eventName
     * @throws ApiException
     * @throws ValidatorException
     */
    public function batchAddOrUpdateClients($payload, int $storeId, string $eventName = null)
    {
        try {
            list ($body, $statusCode, $headers) = $this->sendWithTokenExpiredCatch(
                function () use ($storeId, $payload) {
                    $this->getDefaultApiInstance($storeId)
                        ->batchAddOrUpdateClientsWithHttpInfo('application/json', '4.4', $payload);
                },
                $storeId
            );

            if ($statusCode == 207) {
                $this->loggerHelper->getLogger()->warning('Request partially accepted', ['response_body' => $body]);
            }
        } catch (ApiException $e) {
            $shouldLogException = true;
            if ($eventName == UuidManagement::EVENT) {
                $shouldLogException = $this->loggerHelper->isExcludedFromLogging(
                    Exclude::EXCEPTION_CLIENT_MERGE_FAIL
                );
            }

            if ($shouldLogException) {
                $this->logApiException($e);
            }
            throw $e;
        }
    }

    /**
     * Get default API instance
     *
     * @param int $storeId
     * @return DefaultApi
     * @throws ApiException
     * @throws ValidatorException
     */
    protected function getDefaultApiInstance(int $storeId): DefaultApi
    {
        return $this->getApiInstance('default', $storeId);
    }

    /**
     * Prepare customer params
     *
     * @param Customer|\Magento\Customer\Model\Data\Customer $customer
     * @param int|null $storeId
     * @return array
     */
    public function prepareParams($customer, ?int $storeId = null): array
    {
        $params = [
            'custom_id' => $customer->getId(),
            'email' => $customer->getEmail(),
            'first_name' => $customer->getFirstname(),
            'last_name' => $customer->getLastname()
        ];

        if (is_a($customer, \Magento\Customer\Model\Data\Customer::class)) {
            /** @var \Magento\Customer\Model\Data\Customer $customer */
            $data = $customer->__toArray();
        } else {
            /** @var \Magento\Customer\Model\Customer $customer */
            $data = (array) $customer->getData();
        }

        $selectedAttributes = $this->getEnabledAttributes($storeId);
        foreach ($selectedAttributes as $attribute) {
            if (!isset($data[$attribute])) {
                continue;
            }

            switch ($attribute) {
                case 'default_billing':
                    $defaultAddress = $this->getAddressIfAvailable($this->valOrNull($data['default_billing']));
                    if ($defaultAddress) {
                        $params['phone'] = $this->valOrNull($defaultAddress->getTelephone());
                        $params['city'] = $this->valOrNull($defaultAddress->getCity());
                        $street = $defaultAddress->getStreet();
                        $params['address'] = $this->valOrNull(is_array($street) ? implode(' ', $street) : $street);
                        $params['zip_code'] = $this->valOrNull($defaultAddress->getPostcode());
                        $params['province'] = $this->valOrNull($defaultAddress->getRegion()->getRegion());
                        $params['country_code'] = $this->valOrNull($defaultAddress->getCountryId());
                        $params['company'] = $this->valOrNull($defaultAddress->getCompany());
                    }
                    break;
                case 'dob':
                    $params['birth_date'] = !empty($data['dob']) ? substr($data['dob'], 0, 10) : null;
                    break;
                case 'gender':
                    $params['sex'] = self::MAPPING_GENDER[$data['gender']] ?? null;
                    break;
                case 'display_name':
                case 'avatar_url':
                    $params[$attribute] = $this->valOrNull($data[$attribute]);
                    break;
                default:
                    if (!empty($data[$attribute])) {
                        $params['attributes'][$attribute] = $data[$attribute];
                    }
            }
        }

        return $params;
    }

    /**
     * Get enabled attributes
     *
     * @param int|null $storeId
     * @return array|false|string[]
     */
    public function getEnabledAttributes(?int $storeId = null)
    {
        $attributes = $this->scopeConfig->getValue(
            Attributes::XML_PATH_CUSTOMER_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $attributes ? explode(',', $attributes) : [];
    }

    /**
     * @inheritDoc
     */
    public function getAttributesToSelect(int $storeId): array
    {
        return array_merge(
            $this->getEnabledAttributes($storeId),
            Attributes::REQUIRED
        );
    }

    /**
     * Get address if available
     *
     * @param int $addressId
     * @return AddressInterface|null
     */
    protected function getAddressIfAvailable(int $addressId): ?AddressInterface
    {
        try {
            return $addressId ? $this->addressRepository->getById($addressId) : null;
        } catch (LocalizedException $e) {
            return null;
        }
    }

    /**
     * Return value or null
     *
     * @param string|null $val
     * @return string|null
     */
    protected function valOrNull(?string $val): ?string
    {
        if (empty($val)) {
            return null;
        }

        return empty(trim($val)) ? null : $val;
    }

    /**
     * Mark customers as sent
     *
     * @param int[] $ids
     * @return void
     * @param int $storeId
     */
    public function markCustomersAsSent(array $ids, int $storeId = 0)
    {
        $data = [];
        foreach ($ids as $id) {
            $data[] = [
                'customer_id' => $id,
                'store_id' => $storeId
            ];
        }
        $this->resource->getConnection()->insertOnDuplicate(
            $this->resource->getTableName('synerise_sync_customer'),
            $data
        );
    }
}
