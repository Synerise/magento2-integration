<?php

namespace Synerise\Integration\Helper;

use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\InBodyClientSex;
use Synerise\Integration\Model\Config\Source\Customers\Attributes;
use Magento\Newsletter\Model\Subscriber;

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

    protected $configWriter;
    protected $cacheManager;
    protected $action;
    protected $dateTime;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Api
     */
    protected $apiHelper;
    /**
     * @var Tracking
     */
    protected $trackingHelper;

    protected $addressRepository;

    protected $subscriber;

    public function __construct(
        \Synerise\Integration\ResourceModel\Customer\Action $action,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Cache\Manager $cacheManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Newsletter\Model\Subscriber $subscriber,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        Api $apiHelper,
        Tracking $trackingHelper
    ) {
        $this->addressRepository = $addressRepository;
        $this->subscriber= $subscriber;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->action = $action;
        $this->cacheManager = $cacheManager;
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
        $this->dateTime = $dateTime;
        $this->apiHelper = $apiHelper;
        $this->trackingHelper = $trackingHelper;

        parent::__construct($context);
    }

    /**
     * @param $collection
     * @throws ApiException
     */
    public function addCustomersBatch($collection)
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

            $params = $this->preapreAdditionalParams($customer);
            $params['email'] = $customer->getEmail();
            $params['custom_id'] = $customer->getId();
            $params['attributes'] = !empty($attributes) ? $attributes : null;

            $createAClientInCrmRequests[] = new \Synerise\ApiClient\Model\CreateaClientinCRMRequest($params);
        }

        $this->sendCustomersToSynerise($createAClientInCrmRequests);
        $this->markItemsAsSent($ids);
    }

    /**
     * @param $collection
     * @throws ApiException
     */
    public function addCustomerSubscriptionsBatch($collection)
    {
        $createAClientInCrmRequests = [];

        if (!$collection->count()) {
            return;
        }

        foreach ($collection as $subscription) {
            $email = $subscription->getSubscriberEmail();
            $createAClientInCrmRequests[] = new \Synerise\ApiClient\Model\CreateaClientinCRMRequest(
                [
                    'email' => $email,
                    'uuid' => $this->trackingHelper->genrateUuidByEmail($email),
                    'agreements' => [
                        'email' => $subscription->getSubscriberStatus() == Subscriber::STATUS_SUBSCRIBED ? 1 : 0
                    ]
                ]
            );
        }

        $this->sendCustomersToSynerise($createAClientInCrmRequests);
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string|null $prevUuid
     * @throws LocalizedException
     */
    public function addOrUpdateClient($customer, $prevUuid = null)
    {
        $emailUuid = $this->trackingHelper->genrateUuidByEmail($customer->getEmail());

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
            list ($body, $statusCode, $headers) = $this->apiHelper->getDefaultApiInstance()
                ->batchAddOrUpdateClientsWithHttpInfo(
                    'application/json',
                    '4.4',
                    [
                        new \Synerise\ApiClient\Model\CreateaClientinCRMRequest($params)
                    ]
                );

            if ($statusCode != 202) {
                $this->logger->error('Client update failed');
            } else {
                $this->markItemsAsSent([$customer->getId()]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Client update failed', ['exception' => $e]);
        }
    }

    /**
     * @param $createAClientInCrmRequests
     * @param $ids
     * @throws ApiException
     */
    public function sendCustomersToSynerise($createAClientInCrmRequests)
    {
        list ($body, $statusCode, $headers) = $this->apiHelper->getDefaultApiInstance()
            ->batchAddOrUpdateClientsWithHttpInfo('application/json', '4.4', $createAClientInCrmRequests);

        if ($statusCode != 202) {
            throw new ApiException(sprintf(
                'Invalid Status [%d]',
                $statusCode
            ));
        }
    }

    public function markItemsAsSent($ids)
    {
        $this->action->updateAttributes(
            $ids,
            ['synerise_updated_at' => $this->dateTime->gmtDate()]
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
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return array
     * @throws LocalizedException
     */
    public function preapreAdditionalParams($customer)
    {
        return $this->mapAttributesToParams($customer->getData(), true);
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

    protected function mapAttributesToParams($data, $includeAttributesNode = false)
    {
        $params = [];
        $selectedAttributes = $this->getAttributes();
        foreach ($selectedAttributes as $attribute) {
            if (!isset($data[$attribute])) {
                continue;
            }

            switch ($attribute) {
                case 'default_billing':
                    $defaultAddress = !empty($data['default_billing']) ?
                        $this->addressRepository->getById($data['default_billing']) : null;

                    if ($defaultAddress) {
                        $params['phone'] = $defaultAddress->getTelephone();
                        $params['city'] = $defaultAddress->getCity();
                        $street = $defaultAddress->getStreet();
                        $params['address'] = is_array($street) ? implode(" ", $street) : $street;
                        $params['zip_code'] = $defaultAddress->getPostcode();
                        $params['province'] = $defaultAddress->getRegion()->getRegion();
                        $params['country_code'] = $defaultAddress->getCountryId();
                        $params['company'] = $defaultAddress->getCompany();
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
                case 'displayName':
                case 'avatarUrl':
                    $params[$attribute] = $data[$attribute] ?? null;
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

    public function getAttributes()
    {
        $attributes = $this->scopeConfig->getValue(
            \Synerise\Integration\Helper\Config::XML_PATH_CUSTOMERS_ATTRIBUTES
        );

        return $attributes ? explode(',', $attributes) : [];
    }

    public function getAttributesToSelect()
    {
        $attributes = $this->getAttributes();
        return array_merge(
            $attributes,
            Attributes::REQUIRED
        );
    }
}
