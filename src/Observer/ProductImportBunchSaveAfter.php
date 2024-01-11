<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Synerise\Integration\Helper\Logger;
use \Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\MessageQueue\Publisher\Data\Batch as Publisher;
use Synerise\Integration\SyneriseApi\Sender\Data\Product as Sender;

class ProductImportBunchSaveAfter implements ObserverInterface
{
    public const EVENT = 'catalog_product_import_bunch_save_after';

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
     * @var Publisher
     */
    protected $publisher;

    /**
     * @param Logger $loggerHelper
     * @param Synchronization $synchronizationHelper
     * @param Tracking $trackingHelper
     * @param Publisher $publisher
     */
    public function __construct(
        Logger $loggerHelper,
        Synchronization $synchronizationHelper,
        Tracking $trackingHelper,
        Publisher $publisher
    ) {
        $this->loggerHelper = $loggerHelper;
        $this->synchronizationHelper = $synchronizationHelper;
        $this->trackingHelper = $trackingHelper;
        $this->publisher = $publisher;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $productsByStore = [];
            $bunch = $observer->getEvent()->getData('bunch');
            foreach ($bunch as $product) {
                if (isset($product['entity_id']) && isset($product['store_id'])) {
                    if (!$this->trackingHelper->isEventTrackingAvailable(self::EVENT, $product['store_id'])) {
                        return;
                    }
                    $productsByStore[$product['store_id']][] = $product['entity_id'];
                }
            }

            $enabledStoreIds = $this->synchronizationHelper->getEnabledStores();
            foreach ($productsByStore as $storeId => $entityIds) {
                if (in_array($storeId, $enabledStoreIds)) {
                    $this->publisher->schedule(Sender::MODEL, $entityIds, $storeId);
                }
            }
        } catch (\Exception $e) {
            $this->loggerHelper->getLogger()->error($e);
        }
    }
}
