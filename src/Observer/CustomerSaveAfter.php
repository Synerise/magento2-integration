<?php

namespace Synerise\Integration\Observer;

use Magento\Customer\Model\Customer;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\MessageQueue\Publisher\Data\Item as DataItemPublisher;
use Synerise\Integration\SyneriseApi\Sender\Data\Customer as Sender;

class CustomerSaveAfter implements ObserverInterface
{
    public const EVENT = 'customer_save_after';

    public const EXCLUDED_PATHS = [
        '/newsletter/manage/save/'
    ];

    /**
     * @var Synchronization
     */
    protected $synchronizationHelper;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var Http
     */
    private $request;

    /**
     * @var Sender
     */
    protected $sender;

    /**
     * @var DataItemPublisher
     */
    protected $dataItemPublisher;

    /**
     * @param Synchronization $synchronizationHelper
     * @param Logger $loggerHelper
     * @param Tracking $trackingHelper
     * @param Http $request
     * @param DataItemPublisher $dataItemPublisher
     * @param Sender $sender
     */
    public function __construct(
        Synchronization $synchronizationHelper,
        Logger $loggerHelper,
        Tracking $trackingHelper,
        Http $request,
        DataItemPublisher $dataItemPublisher,
        Sender $sender
    ) {
        $this->synchronizationHelper = $synchronizationHelper;
        $this->loggerHelper = $loggerHelper;
        $this->trackingHelper = $trackingHelper;
        $this->request = $request;
        $this->dataItemPublisher = $dataItemPublisher;
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
        if (in_array($this->request->getPathInfo(), self::EXCLUDED_PATHS)) {
            return;
        }

        if (!$this->synchronizationHelper->isEnabledModel(Sender::MODEL)) {
            return;
        }

        try {
            /** @var Customer $customer */
            $customer = $observer->getCustomer();
            $storeId = $customer->getStoreId();

            if (!$this->trackingHelper->isEventTrackingAvailable(self::EVENT, $storeId)) {
                return;
            }

            if ($this->trackingHelper->isEventMessageQueueAvailable(self::EVENT, $storeId)) {
                $this->dataItemPublisher->publish(
                    Sender::MODEL,
                    (int) $customer->getEntityId(),
                    $storeId
                );
            } else {
                $this->sender->sendItems([$customer], $storeId);
            }
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->loggerHelper->getLogger()->error($e);
            }
        }
    }
}
