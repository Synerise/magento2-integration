<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\Subscriber;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\MessageQueue\Sender\Event;
use Synerise\Integration\MessageQueue\Publisher\Event as EventPublisher;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\MessageQueue\Publisher\Data\Item as DataItemPublisher;
use Synerise\Integration\MessageQueue\Sender\Data\Subscriber as Sender;

class NewsletterSubscriberSaveAfter implements ObserverInterface
{
    const EVENT = 'newsletter_subscriber_save_after';

    /**
     * @var DataItemPublisher
     */
    protected $dataItemPublisher;

    /**
     * @var Sender
     */
    protected $sender;

    /**
     * @var Synchronization
     */
    protected $synchronizationHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var EventPublisher
     */
    protected $eventPublisher;

    /**
     * @var Event
     */
    protected $event;

    public function __construct(
        DataItemPublisher $dataItemPublisher,
        Sender $sender,
        Synchronization $synchronizationHelper,
        Tracking $trackingHelper,
        EventPublisher $eventPublisher,
        Event $event
    ) {
        $this->dataItemPublisher = $dataItemPublisher;
        $this->event = $event;
        $this->synchronizationHelper = $synchronizationHelper;
        $this->trackingHelper = $trackingHelper;
        $this->eventPublisher = $eventPublisher;
        $this->sender = $sender;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->trackingHelper->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        if (!$this->synchronizationHelper->isEnabledModel(Sender::MODEL)) {
            return;
        }

        /** @var Subscriber $subscriber */
        $subscriber = $observer->getEvent()->getDataObject();
        $storeId = $subscriber->getStoreId();
        try {
            if (!$this->trackingHelper->isLoggedIn()) {
                $this->trackingHelper->manageClientUuid($subscriber->getEmail());
            }

            if ($this->trackingHelper->isQueueAvailable(self::EVENT, $storeId)) {
                $this->dataItemPublisher->publish(
                    Sender::MODEL,
                    $subscriber->getEntityId(),
                    $storeId
                );
            } else {
                $this->sender->sendItems([$subscriber], $storeId);
            }
        } catch (ApiException $e) {
        } catch (\Exception $e) {
            $this->trackingHelper->getLogger()->error($e);
        }
    }
}
