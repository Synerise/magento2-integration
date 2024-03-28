<?php

namespace Synerise\Integration\Observer\Data;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\Subscriber;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking\State;
use Synerise\Integration\Helper\Tracking\UuidManagement;
use Synerise\Integration\MessageQueue\Publisher\Data\Item as DataItemPublisher;
use Synerise\Integration\Model\Synchronization\Config;
use Synerise\Integration\Model\Tracking\ConfigFactory;
use Synerise\Integration\SyneriseApi\Sender\Data\Subscriber as Sender;

class SubscriberSave implements ObserverInterface
{
    public const EVENT = 'newsletter_subscriber_save_after';

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

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
     * @var State
     */
    protected $stateHelper;

    /**
     * @var Config
     */
    protected $synchronization;

    /**
     * @var UuidManagement
     */
    protected $uuidHelper;

    /**
     * @param ConfigFactory $configFactory
     * @param DataItemPublisher $dataItemPublisher
     * @param Sender $sender
     * @param Logger $loggerHelper
     * @param State $stateHelper
     * @param Config $synchronization
     * @param UuidManagement $uuidHelper
     */
    public function __construct(
        ConfigFactory $configFactory,
        DataItemPublisher $dataItemPublisher,
        Sender $sender,
        Logger $loggerHelper,
        State $stateHelper,
        Config $synchronization,
        UuidManagement $uuidHelper
    ) {
        $this->configFactory = $configFactory;
        $this->dataItemPublisher = $dataItemPublisher;
        $this->sender = $sender;
        $this->loggerHelper = $loggerHelper;
        $this->stateHelper = $stateHelper;
        $this->synchronization = $synchronization;
        $this->uuidHelper = $uuidHelper;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->synchronization->isModelEnabled(Sender::MODEL)) {
            return;
        }

        /** @var Subscriber $subscriber */
        $subscriber = $observer->getEvent()->getDataObject();
        $storeId = $subscriber->getStoreId();

        $config = $this->configFactory->create($storeId);
        if (!$config->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        try {
            if (!$this->stateHelper->isLoggedIn() && !$this->stateHelper->isAdminStore()) {
                $this->uuidHelper->manageByEmail(
                    $subscriber->getEmail(),
                    $storeId
                );
            }

            if ($config->isEventMessageQueueEnabled(self::EVENT)) {
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
                $this->loggerHelper->error($e);
            }
        }
    }
}
