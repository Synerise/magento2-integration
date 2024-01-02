<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\EventClientAction;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking\UuidManagement;
use Synerise\Integration\MessageQueue\Publisher\Event as EventPublisher;
use Synerise\Integration\SyneriseApi\Sender\Event;
use Synerise\Integration\MessageQueue\Publisher\Event as Publisher;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\SyneriseApi\Sender\Event as EventSender;

class CustomerLogin implements ObserverInterface
{
    public const EVENT = 'customer_login';

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

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
     * @param Logger $loggerHelper
     * @param Tracking $trackingHelper
     * @param UuidManagement $uuidHelper
     * @param Publisher $publisher
     * @param EventSender $sender
     */
    public function __construct(
        Logger $loggerHelper,
        Tracking $trackingHelper,
        UuidManagement $uuidHelper,
        EventPublisher $publisher,
        EventSender $sender
    ) {
        $this->loggerHelper = $loggerHelper;
        $this->trackingHelper = $trackingHelper;
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
        if (!$this->trackingHelper->isEventTrackingAvailable(self::EVENT)) {
            return;
        }

        if ($this->trackingHelper->getContext()->isAdminStore()) {
            return;
        }

        try {
            /** @var \Magento\Customer\Model\Customer $customer */
            $customer = $observer->getEvent()->getCustomer();
            $storeId = $customer->getStoreId();

            $this->uuidHelper->manageByEmail(
                $customer->getEmail(),
                $this->trackingHelper->getContext()->getStoreId()
            );

            $eventClientAction = new EventClientAction([
                'event_salt' => $this->trackingHelper->generateEventSalt(),
                'time' => $this->trackingHelper->getContext()->getCurrentTime(),
                'label' => $this->trackingHelper->getEventLabel(self::EVENT),
                'client' => $this->trackingHelper->prepareClientDataFromCustomer(
                    $customer,
                    $this->trackingHelper->getClientUuid()
                ),
                'params' => $this->trackingHelper->prepareContextParams()
            ]);

            if ($this->trackingHelper->isEventMessageQueueAvailable(self::EVENT, $storeId)) {
                $this->publisher->publish(self::EVENT, $eventClientAction, $storeId);
            } else {
                $this->sender->send(self::EVENT, $eventClientAction, $storeId);
            }
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->loggerHelper->getLogger()->error($e);
            }
        }
    }
}
