<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\Subscriber;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\MessageQueue\Publisher\Event;
use Synerise\Integration\Model\Tracking\ConfigFactory;
use Synerise\Integration\SyneriseApi\Mapper\SubscriberAdd;
use Synerise\Integration\SyneriseApi\Sender\Data\Subscriber as SubscriberSender;

class NewsletterSubscriberDeleteAfter implements ObserverInterface
{
    public const EVENT = 'newsletter_subscriber_delete_after';

    public const EVENT_FOR_CONFIG = 'newsletter_subscriber_save_after';

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var Event
     */
    protected $publisher;

    /**
     * @var SubscriberSender
     */
    protected $sender;
    /**
     * @var SubscriberAdd
     */
    protected $subscriberAdd;

    /**
     * @param ConfigFactory $configFactory
     * @param Logger $loggerHelper
     * @param SubscriberAdd $subscriberAdd
     * @param Event $publisher
     * @param SubscriberSender $sender
     */
    public function __construct(
        ConfigFactory $configFactory,
        Logger $loggerHelper,
        SubscriberAdd $subscriberAdd,
        Event $publisher,
        SubscriberSender $sender
    ) {
        $this->configFactory = $configFactory;
        $this->loggerHelper = $loggerHelper;
        $this->subscriberAdd = $subscriberAdd;
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
        /** @var Subscriber $subscriber */
        $subscriber = $observer->getEvent()->getDataObject();
        $storeId = $subscriber->getStoreId();

        $config = $this->configFactory->create($storeId);
        if (!$config->isEventTrackingEnabled(self::EVENT_FOR_CONFIG)) {
            return;
        }

        try {
            $createAClientInCrmRequest = $this->subscriberAdd->prepareRequest($subscriber, true);

            if ($config->isEventMessageQueueEnabled(self::EVENT_FOR_CONFIG)) {
                $this->publisher->publish(self::EVENT, $createAClientInCrmRequest, $storeId, $subscriber->getId());
            } else {
                $this->sender->deleteItem($createAClientInCrmRequest, $storeId, $subscriber->getId());
            }
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->loggerHelper->error($e);
            }
        }
    }
}
