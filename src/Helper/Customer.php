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
    CONST GENDER = array(
        1 => InBodyClientSex::MALE,
        2 => InBodyClientSex::FEMALE,
        3 => InBodyClientSex::NOT_SPECIFIED
    );

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
    ){
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
        if(!$collection->getSize()) {
            return;
        }

        $ids = [];
        $createAClientInCrmRequests = [];

        if(!$collection->count()) {
            return;
        }

        foreach ($collection as $customer) {
            $ids[] = $customer->getEntityId();

            $createAClientInCrmRequests[] = new \Synerise\ApiClient\Model\CreateaClientinCRMRequest(
                array_merge(
                    $this->prepareIdentityParams($customer),
                    $this->preapreAdditionalParams($customer)
                )
            );
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

        if(!$collection->count()) {
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

    public function addOrUpdateClient($customer, $prevUuid = null)
    {
        $emailUuid = $this->trackingHelper->genrateUuidByEmail($customer->getEmail());

        if($prevUuid && !$this->trackingHelper->isAdminStore() && $prevUuid != $emailUuid) {
            $this->trackingHelper->setClientUuidAndResetCookie((string) $emailUuid);
        }

        $createAClientInCrmRequests = [
            new \Synerise\ApiClient\Model\CreateaClientinCRMRequest([
                'email' => $customer->getEmail(),
                'uuid' => $emailUuid,
                'custom_id' => $customer->getId(),
                'first_name' => $customer->getFirstname(),
                'last_name' => $customer->getLastname(),
                $this->preapreAdditionalParams($customer)
            ])
        ];

        try {
            list ($body, $statusCode, $headers) = $this->apiHelper->getDefaultApiInstance()
                ->batchAddOrUpdateClientsWithHttpInfo('application/json','4.4', $createAClientInCrmRequests);

            if($statusCode != 202) {
                $this->logger->error('Client update failed');
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
            ->batchAddOrUpdateClientsWithHttpInfo('application/json','4.4', $createAClientInCrmRequests);

        if($statusCode != 202) {
            throw new ApiException(sprintf(
                'Invalid Status [%d]',
                $statusCode
            ));
        }
    }

    protected function markItemsAsSent($ids)
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
        $params = [
            'firstname' => $customer->getFirstname(),
            'lastname' => $customer->getLastname(),
        ];

        $attributes = $this->getAttributes();
        $data = (array) $customer;

        foreach($attributes as $attribute) {
            if(!isset($data[$attribute])) {
                continue;
            }

            switch($attribute) {
                case 'default_billing':
                    $defaultAddress = $customer->getDefaultBilling() ?
                        $this->addressRepository->getById($customer->getDefaultBilling()) : null;

                    if($defaultAddress) {
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
                    $params['birth_date'] = $customer->getDob();
                    break;
                case 'gender':
                    $params['sex'] = $this->prepareCustomerGender($customer);
                    break;
                default:
                    $params[$attribute] = $data[$attribute];
            }
        }

        $subscriber = $this->subscriber->loadByEmail($customer->getEmail());
        if($subscriber) {
            $params['agreements'] = ['email' => $subscriber->isSubscribed()];
        }

        return $params;
    }

    public function prepareCustomerGender($customer)
    {
        return isset(self::GENDER[$customer->getGender()]) ? self::GENDER[$customer->getGender()] : null;
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
