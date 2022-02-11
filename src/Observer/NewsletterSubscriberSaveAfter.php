<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;

class NewsletterSubscriberSaveAfter implements ObserverInterface
{
    const EVENT = 'newsletter_subscriber_save_after';

    /**
     * @var \Synerise\Integration\Cron\Synchronization
     */
    protected $synchronization;

    /**
     * @var \Synerise\Integration\Helper\Tracking
     */
    protected $trackingHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Synerise\Integration\Cron\Synchronization $synchronization,
        \Synerise\Integration\Helper\Tracking $trackingHelper
    ) {
        $this->logger = $logger;
        $this->synchronization = $synchronization;
        $this->trackingHelper = $trackingHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if(!$this->trackingHelper->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        $event = $observer->getEvent();
        $subscriber = $event->getDataObject();

        try {
            if(!$this->trackingHelper->isLoggedIn()) {
                $this->trackingHelper->manageClientUuid($subscriber->getEmail());
            }

            $this->synchronization->addItemToQueueByStoreId(
                'subscriber',
                $subscriber->getStoreId(),
                $subscriber->getId()
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to add subscriber to cron queue', ['exception' => $e]);
        }
    }
}