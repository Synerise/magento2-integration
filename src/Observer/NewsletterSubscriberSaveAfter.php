<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\Subscriber;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\Helper\Tracking\UuidManagement;
use Synerise\Integration\MessageQueue\Publisher\Data\Item as DataItemPublisher;
use Synerise\Integration\SyneriseApi\Sender\Data\Subscriber as Sender;

class NewsletterSubscriberSaveAfter implements ObserverInterface
{
    public const EVENT = 'newsletter_subscriber_save_after';

    /**
     * @var DataItemPublisher
     */
    protected $dataItemPublisher;

    /**
     * @var Sender
     */
    protected $sender;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var Synchronization
     */
    protected $synchronizationHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var UuidManagement
     */
    protected $uuidHelper;

    /**
     * @param DataItemPublisher $dataItemPublisher
     * @param Sender $sender
     * @param Logger $loggerHelper
     * @param Synchronization $synchronizationHelper
     * @param Tracking $trackingHelper
     * @param UuidManagement $uuidHelper
     */
    public function __construct(
        DataItemPublisher $dataItemPublisher,
        Sender $sender,
        Logger $loggerHelper,
        Synchronization $synchronizationHelper,
        Tracking $trackingHelper,
        UuidManagement $uuidHelper
    ) {
        $this->dataItemPublisher = $dataItemPublisher;
        $this->sender = $sender;
        $this->loggerHelper = $loggerHelper;
        $this->synchronizationHelper = $synchronizationHelper;
        $this->trackingHelper = $trackingHelper;
        $this->uuidHelper = $uuidHelper;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->synchronizationHelper->isEnabledModel(Sender::MODEL)) {
            return;
        }

        $context = $this->trackingHelper->getContext();

        /** @var Subscriber $subscriber */
        $subscriber = $observer->getEvent()->getDataObject();
        $storeId = $subscriber->getStoreId();


        if (!$this->trackingHelper->isEventTrackingAvailable(self::EVENT, $storeId)) {
            return;
        }

        try {
            if (!$context->isLoggedIn() && !$context->isAdminStore()) {
                $this->uuidHelper->manageByEmail(
                    $subscriber->getEmail(),
                    $this->trackingHelper->getContext()->getStoreId()
                );
            }

            if ($this->trackingHelper->isEventMessageQueueAvailable(self::EVENT, $storeId)) {
                $this->dataItemPublisher->publish(
                    Sender::MODEL,
                    $subscriber->getId(),
                    $storeId
                );
            } else {
                $this->sender->sendItems([$subscriber], $storeId);
            }
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->loggerHelper->getLogger()->error($e);
            }
        }
    }
}
