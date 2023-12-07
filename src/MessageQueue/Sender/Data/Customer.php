<?php

namespace Synerise\Integration\MessageQueue\Sender\Data;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Model\Customer as CustomerModel;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\ApiClient\Model\InBodyClientSex;
use Synerise\Integration\MessageQueue\Sender\AbstractSender;
use Synerise\Integration\Model\Config\Source\Customers\Attributes;
use Synerise\Integration\SyneriseApi\ConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory;

class Customer extends AbstractSender implements SenderInterface
{
    const MODEL = 'customer';
    const ENTITY_ID = 'entity_id';

    const MAPPING_GENDER = [
        1 => InBodyClientSex::MALE,
        2 => InBodyClientSex::FEMALE,
        3 => InBodyClientSex::NOT_SPECIFIED
    ];

    const MAX_PAGE_SIZE = 500;

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
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        AddressRepositoryInterface $addressRepository,
        ScopeConfigInterface $scopeConfig,
        ResourceConnection $resource,
        LoggerInterface $logger,
        ConfigFactory $configFactory,
        InstanceFactory $apiInstanceFactory
    ) {
        $this->addressRepository = $addressRepository;
        $this->scopeConfig = $scopeConfig;
        $this->resource = $resource;

        parent::__construct($logger, $configFactory, $apiInstanceFactory);
    }

    /**
     * @param Collection|CustomerModel[] $collection
     * @param int $storeId
     * @param int|null $websiteId
     * @return void
     * @throws \Synerise\ApiClient\ApiException|ValidatorException
     */
    public function sendItems($collection, int $storeId, ?int $websiteId = null)
    {
        {
            $createAClientInCrmRequests = [];
            $ids = [];

            foreach ($collection as $customer) {
                $createAClientInCrmRequests[] = new CreateaClientinCRMRequest($this->preapreParams($customer, $storeId));
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
     * @param $createAClientInCrmRequests
     * @param $storeId
     * @param bool $isRetry
     * @throws ApiException
     * @throws ValidatorException
     */
    public function batchAddOrUpdateClients($createAClientInCrmRequests, $storeId, $isRetry = false)
    {
        try {
            list ($body, $statusCode, $headers) = $this->getDefaultApiInstance($storeId)
                ->batchAddOrUpdateClientsWithHttpInfo('application/json', '4.4', $createAClientInCrmRequests);

            if (substr($statusCode, 0, 1) != 2) {
                throw new ApiException(sprintf('Invalid Status [%d]', $statusCode));
            } elseif ($statusCode == 207) {
                $this->logger->warning('Request partially accepted', ['response_body' => $body]);
            }
        } catch (ApiException $e) {
            $this->handleApiExceptionAndMaybeUnsetToken($e, ConfigFactory::MODE_SCHEDULE, $storeId);
            if (!$isRetry) {
                $this->batchAddOrUpdateClients($createAClientInCrmRequests, $storeId, true);
            }
        }
    }

    /**
     * @param int $storeId
     * @return mixed
     * @throws ApiException
     * @throws ValidatorException
     */
    protected function getDefaultApiInstance(int $storeId)
    {
        $config = $this->configFactory->getConfig(ConfigFactory::MODE_SCHEDULE, $storeId);
        return $this->apiInstanceFactory->getApiInstance(
            $config->getScopeKey(),
            'default',
            $config
        );
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @return array
     */
    public function preapreParams($customer, $storeId = null)
    {
        $params = [
            'custom_id' => $customer->getId(),
            'email' => $customer->getEmail(),
            'first_name' => $customer->getFirstname(),
            'last_name' => $customer->getLastname()
        ];

        if (is_a($customer, 'Magento\Customer\Model\Data\Customer')) {
            /** @var \Magento\Customer\Model\Data\Customer $customer */
            $data = $customer->__toArray();
        } else {
            /** @var \Magento\Customer\Model\Customer\Interceptor $customer */
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
     * @param $storeId
     * @return array|false|string[]
     */
    public function getEnabledAttributes($storeId = null)
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
     * @param int $addressId
     * @return AddressInterface|null
     */
    protected function getAddressIfAvailable($addressId)
    {
        try {
            return $addressId ? $this->addressRepository->getById($addressId) : null;
        } catch (LocalizedException $e) {
            return null;
        }
    }

    /**
     * @param mixed $val
     * @return mixed
     */
    protected function valOrNull($val)
    {
        if (empty($val)) {
            return null;
        }

        return empty(trim($val)) ? null : $val;
    }

    /**
     * @param int[] $ids
     * @return void
     * @param int $storeId
     */
    public function markCustomersAsSent(array $ids, $storeId = 0)
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
