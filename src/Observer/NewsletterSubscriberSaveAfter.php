<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Newsletter\Model\Subscriber;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Customer;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\ResourceModel\Cron\Queue as QueueResourceModel;

class NewsletterSubscriberSaveAfter implements ObserverInterface
{
    const EVENT = 'newsletter_subscriber_save_after';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var QueueResourceModel
     */
    protected $queueResourceModel;

    /**
     * @var Customer
     */
    protected $customerHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    public function __construct(
        LoggerInterface $logger,
        Customer $customerHelper,
        Tracking $trackingHelper,
        QueueResourceModel $queueResourceModel
    ) {
        $this->logger = $logger;
        $this->customerHelper = $customerHelper;
        $this->trackingHelper = $trackingHelper;
        $this->queueResourceModel = $queueResourceModel;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->trackingHelper->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        $event = $observer->getEvent();
        /** @var Subscriber $subscriber */
        $subscriber = $event->getDataObject();

        try {
            if (!$this->trackingHelper->isLoggedIn()) {
                $this->trackingHelper->manageClientUuid($subscriber->getEmail());
            }

            $this->customerHelper->sendCustomersToSynerise(
                [$this->customerHelper->prepareRequestFromSubscription($subscriber)],
                $subscriber->getStoreId()
            );

            $this->customerHelper->markSubscribersAsSent([
                $subscriber->getId()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Subscription update request failed', ['exception' => $e]);
            $this->addItemToQueue($subscriber);
        }
    }

    /**
     * @param $subscriber
     */
    protected function addItemToQueue($subscriber)
    {
        try {
            $this->queueResourceModel->addItem(
                'subscriber',
                $subscriber->getStoreId(),
                $subscriber->getId()
            );
        } catch (LocalizedException $e) {
            $this->logger->error('Adding subscription item to queue failed', ['exception' => $e]);
        }
    }
}
