<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\Subscriber;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\MessageQueue\Publisher\Event;
use Synerise\Integration\SyneriseApi\Sender\Data\Subscriber as SubscriberSender;

class NewsletterSubscriberDeleteAfter implements ObserverInterface
{
    public const EVENT = 'newsletter_subscriber_delete_after';

    public const EVENT_FOR_CONFIG = 'newsletter_subscriber_save_after';

    /**
     * @var Logger
     */
    protected $loggerHelper;

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

    /**
     * @param Logger $loggerHelper
     * @param Tracking $trackingHelper
     * @param Event $publisher
     * @param SubscriberSender $sender
     */
    public function __construct(
        Logger $loggerHelper,
        Tracking $trackingHelper,
        Event $publisher,
        SubscriberSender $sender
    ) {
        $this->loggerHelper = $loggerHelper;
        $this->trackingHelper = $trackingHelper;
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
        if (!$this->trackingHelper->isEventTrackingAvailable(self::EVENT_FOR_CONFIG)) {
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

            if ($this->trackingHelper->isEventMessageQueueAvailable(self::EVENT_FOR_CONFIG, $storeId)) {
                $this->publisher->publish(self::EVENT, $createAClientInCrmRequest, $storeId, $subscriber->getId());
            } else {
                $this->sender->deleteItem($createAClientInCrmRequest, $storeId, $subscriber->getId());
            }
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->loggerHelper->getLogger()->error($e);
            }
        }
    }
}
