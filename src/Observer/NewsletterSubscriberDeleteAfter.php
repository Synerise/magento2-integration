<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\Subscriber;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;

class NewsletterSubscriberDeleteAfter implements ObserverInterface
{
    const EVENT = 'newsletter_subscriber_delete_after';

    const EVENT_FOR_CONFIG = 'newsletter_subscriber_save_after';

    /**
     * @var \Synerise\Integration\Helper\Tracking
     */
    protected $trackingHelper;

    /**
     * @var \Synerise\Integration\MessageQueue\Publisher\Event
     */
    protected $publisher;

    /**
     * @var \Synerise\Integration\MessageQueue\Sender\Event
     */
    protected $sender;

    public function __construct(
        \Synerise\Integration\Helper\Tracking $trackingHelper,
        \Synerise\Integration\MessageQueue\Publisher\Event $publisher,
        \Synerise\Integration\MessageQueue\Sender\Event $sender
    ) {
        $this->trackingHelper = $trackingHelper;
        $this->publisher = $publisher;
        $this->sender = $sender;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->trackingHelper->isEventTrackingEnabled(self::EVENT_FOR_CONFIG)) {
            return;
        }

        $event = $observer->getEvent();

        /** @var Subscriber $subscriber */
        $subscriber = $event->getDataObject();
        $storeId = $subscriber->getStoreId();

        try {
            $createAClientInCrmRequest = new CreateaClientinCRMRequest([
                'email' => $subscriber->getSubscriberEmail(),
                'agreements' => ['email' => 0]
            ]);

            if ($this->trackingHelper->isQueueAvailable(self::EVENT_FOR_CONFIG, $storeId)) {
                $this->publisher->publish(self::EVENT, $createAClientInCrmRequest, $storeId, $subscriber->getId());
            } else {
                $this->sender->send(self::EVENT, $createAClientInCrmRequest, $storeId);
            }
        } catch (ApiException $e) {
        } catch (\Exception $e) {
            $this->trackingHelper->getLogger()->error($e);
        }
    }
}
