<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\Helper\Tracking\State;
use Synerise\Integration\Helper\Tracking\UuidManagement;
use Synerise\Integration\MessageQueue\Publisher\Event as EventPublisher;
use Synerise\Integration\Model\Tracking\ConfigFactory;
use Synerise\Integration\SyneriseApi\Mapper\CustomerEvent;
use Synerise\Integration\SyneriseApi\Sender\Event;
use Synerise\Integration\MessageQueue\Publisher\Event as Publisher;
use Synerise\Integration\SyneriseApi\Sender\Event as EventSender;

class CustomerLogin implements ObserverInterface
{
    public const EVENT = 'customer_login';

    /**
     * @var CustomerEvent
     */
    protected $customerEvent;


    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var Cookie
     */
    protected $cookieHelper;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var State
     */
    protected $stateHelper;

    /**
     * @var UuidManagement
     */
    protected $uuidHelper;

    /**
     * @var Publisher
     */
    protected $publisher;

    /**
     * @var Event
     */
    protected $sender;

    /**
     * @param CustomerEvent $customerEvent
     * @param ConfigFactory $configFactory
     * @param Cookie $cookieHelper
     * @param Logger $loggerHelper
     * @param State $stateHelper
     * @param UuidManagement $uuidHelper
     * @param Publisher $publisher
     * @param EventSender $sender
     */
    public function __construct(
        CustomerEvent $customerEvent,
        ConfigFactory $configFactory,
        Cookie $cookieHelper,
        Logger $loggerHelper,
        State $stateHelper,
        UuidManagement $uuidHelper,
        EventPublisher $publisher,
        EventSender $sender
    ) {
        $this->customerEvent = $customerEvent;
        $this->configFactory = $configFactory;
        $this->cookieHelper = $cookieHelper;
        $this->loggerHelper = $loggerHelper;
        $this->stateHelper = $stateHelper;
        $this->uuidHelper = $uuidHelper;
        $this->publisher = $publisher;
        $this->sender = $sender;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->stateHelper->isAdminStore()) {
            return;
        }

        try {
            /** @var \Magento\Customer\Model\Customer $customer */
            $customer = $observer->getEvent()->getCustomer();
            $storeId = $customer->getStoreId();

            $config = $this->configFactory->create($storeId);
            if (!$config->isEventTrackingEnabled(self::EVENT)) {
                return;
            }

            $this->uuidHelper->manageByEmail(
                $customer->getEmail(),
                $storeId
            );

            $eventClientAction = $this->customerEvent->prepareRequest(
                self::EVENT,
                $customer,
                $this->cookieHelper->getSnrsUuid()
            );

            if ($config->isEventMessageQueueEnabled(self::EVENT)) {
                $this->publisher->publish(self::EVENT, $eventClientAction, $storeId);
            } else {
                $this->sender->send(self::EVENT, $eventClientAction, $storeId);
            }
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->loggerHelper->error($e);
            }
        }
    }
}
