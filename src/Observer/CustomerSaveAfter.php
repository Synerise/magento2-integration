<?php

namespace Synerise\Integration\Observer;

use Magento\Customer\Model\Customer;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\MessageQueue\Publisher\Data\Item as DataItemPublisher;
use Synerise\Integration\SyneriseApi\Sender\Data\Customer as Sender;

class CustomerSaveAfter implements ObserverInterface
{
    const EVENT = 'customer_save_after';

    const EXCLUDED_PATHS = [
        '/newsletter/manage/save/'
    ];

    /**
     * @var Synchronization
     */
    protected $synchronizationHelper;

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

    public function __construct(
        Synchronization $synchronizationHelper,
        Tracking $trackingHelper,
        Http $request,
        DataItemPublisher $dataItemPublisher,
        Sender $sender
    ) {
        $this->synchronizationHelper = $synchronizationHelper;
        $this->trackingHelper = $trackingHelper;
        $this->request = $request;
        $this->dataItemPublisher = $dataItemPublisher;
        $this->sender = $sender;
    }

    public function execute(Observer $observer)
    {
        if (!$this->trackingHelper->isLiveEventTrackingEnabled(self::EVENT)) {
            return;
        }

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

            if ($this->trackingHelper->isQueueAvailable(self::EVENT, $storeId)) {
                $this->dataItemPublisher->publish(
                    Sender::MODEL,
                    (int) $customer->getEntityId(),
                    $storeId
                );
            } else {
                $this->sender->sendItems([$customer], $storeId);
            }
        } catch (\Exception $e) {
            if(!$e instanceof ApiException) {
                $this->trackingHelper->getLogger()->error($e);
            }
        }
    }
}
