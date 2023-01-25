<?php

namespace Synerise\Integration\Helper\Update;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\Api\DefaultApi;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\ApiClient\Model\InBodyClientSex;
use Synerise\Integration\Helper\AbstractDefaultApiAction;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Api\DefaultApiFactory;
use Synerise\Integration\Helper\Data\Context as ContextHelper;
use Synerise\Integration\Helper\Identity;
use Synerise\Integration\Helper\Event\AbstractEvent;
use Synerise\Integration\Model\Config\Source\Customers\Attributes;

class Client extends AbstractDefaultApiAction
{
    const UPDATE_GENDER = [
        1 => InBodyClientSex::MALE,
        2 => InBodyClientSex::FEMALE,
        3 => InBodyClientSex::NOT_SPECIFIED
    ];

    const XML_PATH_CUSTOMERS_ATTRIBUTES = 'synerise/customer/attributes';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection;

    /**
     * @var LoggerInterface
     */
    protected $logger;
    
    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var AddressRepositoryInterface
     */
    protected $addressRepository;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ResourceConnection $resource,
        LoggerInterface $logger,
        DateTime $dateTime,
        AddressRepositoryInterface $addressRepository,
        Api $apiHelper,
        DefaultApiFactory $defaultApiFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->connection = $resource->getConnection();
        $this->logger = $logger;
        $this->dateTime = $dateTime;
        $this->addressRepository = $addressRepository;

        parent::__construct($apiHelper, $defaultApiFactory);
    }

    /**
     * @param CreateaClientinCRMRequest[] $createAClientInCrmRequests
     * @param int|null $storeId
     * @return array
     * @throws ApiException
     * @throws ValidatorException
     */
    public function sendBatchAddOrUpdateClients(array $createAClientInCrmRequests, int $storeId = null)
    {
        list ($body, $statusCode, $headers) = $this->getDefaultApiInstance($storeId)
            ->batchAddOrUpdateClientsWithHttpInfo('application/json', '4.4', $createAClientInCrmRequests);

        if (substr($statusCode, 0, 1) != 2) {
            throw new ApiException(sprintf('Invalid Status [%d]', $statusCode));
        } elseif ($statusCode == 207) {
            $this->logger->debug('Request accepted with errors', ['response' => $body]);
        }

        return [$body, $statusCode, $headers];
    }

    /**
     * @param CreateaClientinCRMRequest $createAClientInCrmRequest
     * @param int|null $storeId
     * @return array
     * @throws ApiException
     */
    public function sendCreateClient(CreateaClientinCRMRequest $createAClientInCrmRequest, ?int $storeId = null): array
    {
        return $this->getDefaultApiInstance($storeId)
            ->createAClientInCrmWithHttpInfo('4.4', $createAClientInCrmRequest);
    }

    /**
     * @param \Magento\Customer\Model\Customer|\Magento\Customer\Model\Data\Customer $customer
     * @param null $uuid
     * @param null $storeId
     * @return CreateaClientinCRMRequest
     */
    public function prepareCreateClientRequest($customer, ?string $uuid = null, ?int $storeId = null): CreateaClientinCRMRequest
    {
        $params = [
            'custom_id' => $customer->getId(),
            'email' => $customer->getEmail(),
            'uuid' => $uuid ?: Identity::generateUuidByEmail($customer->getEmail()),
            'first_name' => $customer->getFirstname(),
            'last_name' => $customer->getLastname()
        ];

        if (is_a($customer, 'Magento\Customer\Model\Data\Customer')) {
            $data = $customer->__toArray();
        } else {
            $data = (array) $customer->getData();
        }

        $selectedAttributes = $this->getAttributes($storeId);
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
                        $params['address'] = is_array($street) ? implode(" ", $street) : $this->valOrNull($street);
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
                    $params['sex'] = self::UPDATE_GENDER[$data['gender']] ?? null;
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

        return new CreateaClientinCRMRequest($params);
    }

    public function getAttributes($storeId = null)
    {
        $attributes = $this->scopeConfig->getValue(
            self::XML_PATH_CUSTOMERS_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $attributes ? explode(',', $attributes) : [];
    }

    public function getAttributesToSelect($storeId)
    {
        $attributes = $this->getAttributes($storeId);
        return array_merge(
            $attributes,
            Attributes::REQUIRED
        );
    }

    /**
     * @param int $addressId
     * @return \Magento\Customer\Api\Data\AddressInterface|null
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
        if(empty($val)){
            return null;
        }

        return empty(trim($val)) ? null : $val;
    }

    /**
     * @param int[] $ids
     * @return void
     * @param int $storeId
     */
    public function markAsSent(array $ids, $storeId = 0)
    {
        $timestamp = $this->dateTime->gmtDate();
        $data = [];
        foreach ($ids as $id) {
            $data[] = [
                'synerise_updated_at' => $timestamp,
                'customer_id' => $id,
                'store_id' => $storeId
            ];
        }
        $this->connection->insertOnDuplicate(
            $this->connection->getTableName('synerise_sync_customer'),
            $data
        );
    }
}