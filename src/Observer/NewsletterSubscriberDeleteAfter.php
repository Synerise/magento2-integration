<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\Subscriber;

class NewsletterSubscriberDeleteAfter implements ObserverInterface
{
    const EVENT = 'newsletter_subscriber_save_after';

    /**
     * @var \Synerise\Integration\Cron\Synchronization
     */
    protected $synchronization;

    /**
     * @var \Synerise\Integration\Helper\Customer
     */
    private $customerHelper;

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
        \Synerise\Integration\Helper\Customer $customerHelper,
        \Synerise\Integration\Helper\Tracking $trackingHelper
    ) {
        $this->logger = $logger;
        $this->synchronization = $synchronization;
        $this->customerHelper = $customerHelper;
        $this->trackingHelper = $trackingHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->trackingHelper->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        $event = $observer->getEvent();

        /** @var Subscriber $subscriber */
        $subscriber = $event->getDataObject();

        try {
            $createAClientInCrmRequests = [
                new \Synerise\ApiClient\Model\CreateaClientinCRMRequest([
                    'email' => $subscriber->getSubscriberEmail(),
                    'agreements' => ['email' =>  0]
                ])
            ];

            $this->customerHelper->sendCustomersToSynerise($createAClientInCrmRequests);
        } catch (\Exception $e) {
            $this->logger->error('Failed to unsubscribe user', ['exception' => $e]);
        }
    }
}
