<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\Subscriber;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\MessageQueue\Publisher\Event;
use Synerise\Integration\SyneriseApi\Sender\Data\Subscriber as SubscriberSender;

class NewsletterSubscriberDeleteAfter implements ObserverInterface
{
    const EVENT = 'newsletter_subscriber_delete_after';

    const EVENT_FOR_CONFIG = 'newsletter_subscriber_save_after';

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var Event
     */
    protected $publisher;

    /**
     * @var SubscriberSender
     */
    protected $sender;

    public function __construct(
        Tracking $trackingHelper,
        Event $publisher,
        SubscriberSender $sender
    ) {
        $this->trackingHelper = $trackingHelper;
        $this->publisher = $publisher;
        $this->sender = $sender;
    }

    public function execute(Observer $observer)
    {
        if (!$this->trackingHelper->isEventTrackingEnabled(self::EVENT_FOR_CONFIG)) {
            return;
        }

        /** @var Subscriber $subscriber */
        $subscriber = $observer->getEvent()->getDataObject();
        $storeId = $subscriber->getStoreId();

        try {
            $createAClientInCrmRequest = new CreateaClientinCRMRequest([
                'email' => $subscriber->getSubscriberEmail(),
                'agreements' => ['email' => 0]
            ]);

            if ($this->trackingHelper->isQueueAvailable(self::EVENT_FOR_CONFIG, $storeId)) {
                $this->publisher->publish(self::EVENT, $createAClientInCrmRequest, $storeId, $subscriber->getId());
            } else {
                $this->sender->deleteItem($createAClientInCrmRequest, $storeId, $subscriber->getId());
            }
        } catch (\Exception $e) {
            if(!$e instanceof ApiException) {
                $this->trackingHelper->getLogger()->error($e);
            }
        }
    }
}
