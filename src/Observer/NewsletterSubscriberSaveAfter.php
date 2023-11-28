<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\Subscriber;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Event;
use Synerise\Integration\Helper\Queue;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\Model\Synchronization\MessageQueue\Data\Single\Publisher;
use Synerise\Integration\Model\Synchronization\Sender\Subscriber as Sender;

class NewsletterSubscriberSaveAfter implements ObserverInterface
{
    const EVENT = 'newsletter_subscriber_save_after';

    /**
     * @var Publisher
     */
    protected $publisher;

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
     * @var Queue
     */
    protected $queueHelper;

    /**
     * @var Event
     */
    protected $eventHelper;

    public function __construct(
        Publisher $publisher,
        Sender $sender,
        Synchronization $synchronizationHelper,
        Tracking $trackingHelper,
        Queue $queueHelper,
        Event $eventHelper
    ) {
        $this->publisher = $publisher;
        $this->sender = $sender;
        $this->synchronizationHelper = $synchronizationHelper;
        $this->trackingHelper = $trackingHelper;
        $this->queueHelper = $queueHelper;
        $this->eventHelper = $eventHelper;
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

        $event = $observer->getEvent();
        /** @var Subscriber $subscriber */
        $subscriber = $event->getDataObject();
        $storeId = $subscriber->getStoreId();
        try {
            if (!$this->trackingHelper->isLoggedIn()) {
                $this->trackingHelper->manageClientUuid($subscriber->getEmail());
            }

            $createAClientInCrmRequest = $this->sender->prepareRequestFromSubscription($subscriber);

            if ($this->queueHelper->isQueueAvailable(self::EVENT, $storeId)) {
                $this->queueHelper->publishEvent(self::EVENT, $createAClientInCrmRequest, $storeId, $subscriber->getId());
            } else {
                $this->eventHelper->sendEvent(self::EVENT, $createAClientInCrmRequest, $storeId, $subscriber->getId());
            }
        } catch (ApiException $e) {
            $this->addItemToQueue($subscriber);
        } catch (\Exception $e) {
            $this->trackingHelper->getLogger()->error($e);
            $this->addItemToQueue($subscriber);
        }
    }

    /**
     * @param $subscriber
     */
    protected function addItemToQueue($subscriber)
    {
        $this->publisher->publish(
            Sender::MODEL,
            $subscriber->getId(),
            $subscriber->getStoreId()
        );
    }
}
