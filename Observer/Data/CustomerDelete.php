<?php

namespace Synerise\Integration\Observer\Data;

use Magento\Customer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\ValidatorException;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\MessageQueue\Publisher\Event as EventPublisher;
use Synerise\Integration\Model\Config\Source\CustomerDeleteBehavior;
use Synerise\Integration\Model\Synchronization\Config;
use Synerise\Integration\Model\Tracking\ConfigFactory;
use Synerise\Integration\SyneriseApi\Mapper\Event\CustomerDelete as EventMapper;
use Synerise\Integration\SyneriseApi\Sender\Event as EventSender;
use Synerise\Integration\SyneriseApi\Sender\Data\Customer as CustomerSender;

class CustomerDelete implements ObserverInterface
{
    public const EVENT = 'customer_delete';

    public const REQUEST = 'customer_delete_request';

    /**
     * @var Config
     */
    protected $synchronization;

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var CustomerSender
     */
    protected $customerSender;
    
    /**
     * @var EventMapper
     */
    protected $eventMapper;
    /**
     * @var EventPublisher
     */
    protected $eventPublisher;
    
    /**
     * @var EventSender
     */
    protected $eventSender;

    /**
     * @param Config $synchronization
     * @param ConfigFactory $configFactory
     * @param Logger $loggerHelper
     * @param CustomerSender $customerSender
     * @param EventMapper $eventMapper
     * @param EventPublisher $eventPublisher
     * @param EventSender $eventSender
     */
    public function __construct(
        Config $synchronization,
        ConfigFactory $configFactory,
        Logger $loggerHelper,
        CustomerSender $customerSender,
        EventMapper $eventMapper,
        EventPublisher $eventPublisher,
        EventSender $eventSender
    ) {
        $this->synchronization = $synchronization;
        $this->configFactory = $configFactory;
        $this->loggerHelper = $loggerHelper;
        $this->customerSender = $customerSender;
        $this->eventMapper = $eventMapper;
        $this->eventPublisher = $eventPublisher;
        $this->eventSender = $eventSender;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->synchronization->isModelEnabled(CustomerSender::MODEL)) {
            return;
        }

        try {
            /** @var Customer $customer */
            $customer = $observer->getCustomer();
            $storeId = $customer->getStoreId();

            $config = $this->configFactory->create($storeId);
            if (!$config->isEventTrackingEnabled(self::EVENT)) {
                return;
            }

            if ($config->getCustomerDeleteBehavior() == CustomerDeleteBehavior::REMOVE) {
                $this->handleRequest(
                    $customer->getEntityId(),
                    $storeId,
                    $config->isEventMessageQueueEnabled(self::EVENT)
                );
            } elseif ($config->getCustomerDeleteBehavior() == CustomerDeleteBehavior::SEND_EVENT) {
                $this->handleEvent(
                    $customer->getEmail(),
                    $storeId,
                    $config->isEventMessageQueueEnabled(self::EVENT)
                );
            }

        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->loggerHelper->error($e);
            }
        }
    }

    /**
     * Handle customer delete as event
     *
     * @param string $email
     * @param int $storeId
     * @param bool $queueEnabled
     * @return void
     * @throws ApiException
     * @throws ValidatorException
     */
    protected function handleEvent(string $email, int $storeId, bool $queueEnabled)
    {
        $clientDeleteAccount = $this->eventMapper->prepareRequest($email);
        if ($queueEnabled) {
            $this->eventPublisher->publish(self::EVENT, $clientDeleteAccount, $storeId);
        } else {
            $this->eventSender->send(self::EVENT, $clientDeleteAccount, $storeId);
        }
    }

    /**
     * Handle customer delete as request
     *
     * @param int $customerId
     * @param int $storeId
     * @param bool $queueEnabled
     * @return void
     * @throws ApiException
     * @throws ValidatorException
     */
    protected function handleRequest(int $customerId, int $storeId, bool $queueEnabled)
    {
        if ($queueEnabled) {
            $this->eventPublisher->publish(self::REQUEST, $customerId, $storeId, $customerId);
        } else {
            $this->customerSender->deleteItem($customerId, $storeId, $customerId);
        }
    }
}
