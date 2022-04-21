<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Cron\Synchronization;
use Synerise\Integration\Helper\Customer;
use Synerise\Integration\Helper\Tracking;

class NewsletterSubscriberSaveAfter implements ObserverInterface
{
    const EVENT = 'newsletter_subscriber_save_after';

    /**
     * @var Synchronization
     */
    protected $synchronization;

    /**
     * @var Customer
     */
    private $customerHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        LoggerInterface $logger,
        Synchronization $synchronization,
        Customer $customerHelper,
        Tracking $trackingHelper
    ) {
        $this->logger = $logger;
        $this->synchronization = $synchronization;
        $this->customerHelper = $customerHelper;
        $this->trackingHelper = $trackingHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->trackingHelper->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        $event = $observer->getEvent();
        $subscriber = $event->getDataObject();

        try {
            if (!$this->trackingHelper->isLoggedIn()) {
                $this->trackingHelper->manageClientUuid($subscriber->getEmail());
            }

            $this->customerHelper->sendCustomersToSynerise([
                $this->customerHelper->prepareRequestFromSubscription($subscriber)
            ]);

            $this->customerHelper->markSubscribersAsSent([
                $subscriber->getId()
            ]);

        } catch (\Exception $e) {
            $this->synchronization->addItemToQueueByStoreId(
                'subscriber',
                $subscriber->getStoreId(),
                $subscriber->getId()
            );

            $this->logger->error('Subscription update request failed', ['exception' => $e]);
        }
    }
}
