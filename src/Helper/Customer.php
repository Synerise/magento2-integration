<?php

namespace Synerise\Integration\Helper;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\ApiClient\Model\InBodyClientSex;
use Synerise\Integration\Model\Config\Source\Customers\Attributes;

class Customer extends \Magento\Framework\App\Helper\AbstractHelper
{
    const UPDATE_GENDER = [
        1 => InBodyClientSex::MALE,
        2 => InBodyClientSex::FEMALE,
        3 => InBodyClientSex::NOT_SPECIFIED
    ];

    const EVENT_GENDER = [
        1 => 2,
        2 => 1,
        3 => 0
    ];

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var Api
     */
    protected $apiHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        ResourceConnection $resource,
        DateTime $dateTime,
        StoreManagerInterface $storeManager,
        AddressRepositoryInterface $addressRepository,
        Api $apiHelper,
        Tracking $trackingHelper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->connection = $resource->getConnection();
        $this->dateTime = $dateTime;
        $this->storeManager = $storeManager;
        $this->addressRepository = $addressRepository;
        $this->apiHelper = $apiHelper;
        $this->trackingHelper = $trackingHelper;

        parent::__construct($context);
    }

    /**
     * @param $collection
     * @throws ApiException|\Exception
     */
    public function addCustomersBatch($collection, $storeId)
    {
        if (!$collection->getSize()) {
            return;
        }

        $ids = [];
        $createAClientInCrmRequests = [];

        if (!$collection->count()) {
            return;
        }

        foreach ($collection as $customer) {
            $ids[] = $customer->getEntityId();

            $params = $this->preapreAdditionalParams($customer, $storeId);
            $params['email'] = $customer->getEmail();
            $params['custom_id'] = $customer->getId();

            $createAClientInCrmRequests[] = new CreateaClientinCRMRequest($params);
        }

        $this->sendCustomersToSynerise($createAClientInCrmRequests, $storeId);
        $this->markCustomersAsSent($ids, $storeId);
    }

    /**
     * @param Collection $collection
     * @throws ApiException
     */
    public function addCustomerSubscriptionsBatch($collection, $storeId)
    {
        if (!$collection->count()) {
            return;
        }

        $requests = [];
        foreach ($collection as $subscriber) {
            $requests[] = $this->prepareRequestFromSubscription($subscriber);
        }

        $this->sendCustomersToSynerise($requests, $storeId);
    }

    /**
     * @param Subscriber $subscriber
     * @return CreateaClientinCRMRequest
     */
    public function prepareRequestFromSubscription($subscriber)
    {
        $email = $subscriber->getSubscriberEmail();
        return new CreateaClientinCRMRequest(
            [
                'email' => $email,
                'uuid' => $this->trackingHelper->generateUuidByEmail($email),
                'agreements' => [
                    'email' => $subscriber->getSubscriberStatus() == Subscriber::STATUS_SUBSCRIBED ? 1 : 0
                ]
            ]
        );
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string|null $prevUuid
     */
    public function addOrUpdateClient($customer, $prevUuid = null)
    {
        $emailUuid = $this->trackingHelper->generateUuidByEmail($customer->getEmail());

        if ($prevUuid && !$this->trackingHelper->isAdminStore() && $prevUuid != $emailUuid) {
            $this->trackingHelper->setClientUuidAndResetCookie((string) $emailUuid);
        }
        $params = $this->preapreAdditionalParams($customer);

        $params['email'] = $customer->getEmail();
        $params['custom_id'] = $customer->getId();
        $params['uuid'] = $emailUuid;
        $params['first_name'] = $customer->getFirstname();
        $params['last_name'] = $customer->getLastname();

        try {
            list ($body, $statusCode, $headers) = $this->apiHelper->getDefaultApiInstance($customer->getStoreId())
                ->batchAddOrUpdateClientsWithHttpInfo(
                    'application/json',
                    '4.4',
                    [
                        new CreateaClientinCRMRequest($params)
                    ]
                );

            if ($statusCode != 202) {
                $this->_logger->error('Client update failed');
            } else {
                $this->markCustomersAsSent([$customer->getId()], $customer->getStoreId());
            }
        } catch (\Exception $e) {
            $this->_logger->error('Client update failed', ['exception' => $e]);
        }
    }

    /**
     * @param $createAClientInCrmRequests
     * @param $storeId
     * @throws ApiException
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    public function sendCustomersToSynerise($createAClientInCrmRequests, $storeId)
    {
        list ($body, $statusCode, $headers) = $this->apiHelper->getDefaultApiInstance($storeId)
            ->batchAddOrUpdateClientsWithHttpInfo('application/json', '4.4', $createAClientInCrmRequests);

        if (substr($statusCode, 0, 1) != 2) {
            throw new ApiException(sprintf('Invalid Status [%d]', $statusCode));
        } elseif ($statusCode == 207) {
            $this->_logger->debug('Request accepted with errors', ['response' => $body]);
        }
    }

    /**
     * @param int[] $ids
     */
    public function markSubscribersAsSent($ids)
    {
        $timestamp = $this->dateTime->gmtDate();
        $data = [];
        foreach ($ids as $id) {
            $data[] = [
                'synerise_updated_at' => $timestamp,
                'subscriber_id' => $id
            ];
        }

        $this->connection->insertOnDuplicate(
            $this->connection->getTableName('synerise_sync_subscriber'),
            $data
        );
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param null|string $uuid
     * @return array
     */
    public function prepareIdentityParams($customer, $uuid = null)
    {
        return [
            'email' => $customer->getEmail(),
            'customId' => $customer->getId(),
            'uuid' => $uuid
        ];
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @return array
     */
    public function preapreAdditionalParams($customer, $storeId = null)
    {
        return $this->mapAttributesToParams($customer->getData(), $storeId, true);
    }

    public function preapreParamsForEvent($customer)
    {
        if (is_a($customer, 'Magento\Customer\Model\Data\Customer')) {
            /** @var \Magento\Customer\Model\Data\Customer $customer */
            $data = $customer->__toArray();
        } else {
            /** @var \Magento\Customer\Model\Customer\Interceptor $customer */
            $data = (array) $customer->getData();
        }

        $params = $this->mapAttributesToParams($data);
        $params['applicationName'] = $this->trackingHelper->getApplicationName();
        $params['firstname'] = $customer->getFirstname();
        $params['lastname'] = $customer->getLastname();

        return $params;
    }

    protected function mapAttributesToParams($data, $storeId = null, $includeAttributesNode = false)
    {
        $params = [];
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
                        $street = $this->valOrNull($defaultAddress->getStreet());
                        $params['address'] = is_array($street) ? implode(" ", $street) : $street;
                        $params['zip_code'] = $this->valOrNull($defaultAddress->getPostcode());
                        $params['province'] = $this->valOrNull($defaultAddress->getRegion()->getRegion());
                        $params['country_code'] = $this->valOrNull($defaultAddress->getCountryId());
                        $params['company'] = $this->valOrNull($defaultAddress->getCompany());
                    }
                    break;
                case 'dob':
                    if ($includeAttributesNode) {
                        $params['birth_date'] = !empty($data['dob']) ? substr($data['dob'], 0, 10) : null;
                    } else {
                        $params['birthdate'] = !empty($data['dob']) ? substr($data['dob'], 0, 10) : null;
                    }
                    break;
                case 'gender':
                    if ($includeAttributesNode) {
                        $params['sex'] = self::UPDATE_GENDER[$data['gender']] ?? null;
                    } else {
                        $params['sex'] = self::EVENT_GENDER[$data['gender']] ?? null;
                    }
                    break;
                case 'display_name':
                case 'avatar_url':
                    $params[$attribute] = $this->valOrNull($data[$attribute]);
                    break;
                default:
                    if (!empty($data[$attribute])) {
                        if ($includeAttributesNode) {
                            $params['attributes'][$attribute] = $data[$attribute];
                        } else {
                            $params[$attribute] = $data[$attribute];
                        }
                    }
            }
        }

        return $params;
    }

    public function getAttributes($storeId = null)
    {
        $attributes = $this->scopeConfig->getValue(
            \Synerise\Integration\Helper\Config::XML_PATH_CUSTOMERS_ATTRIBUTES,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
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
        return empty(trim($val)) ? null : $val;
    }

    /**
     * @param int $storeId
     * @return int
     * @throws NoSuchEntityException
     */
    public function getWebsiteIdByStoreId(int $storeId)
    {
        return $this->storeManager->getStore($storeId)->getWebsiteId();
    }

    /**
     * @param int[] $ids
     * @return void
     * @param int $storeId
     */
    public function markCustomersAsSent(array $ids, $storeId = 0)
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
