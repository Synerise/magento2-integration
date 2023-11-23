<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;
use \Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\Model\Synchronization\MessageQueue\Data\Batch\Publisher;
use Synerise\Integration\Model\Synchronization\Sender\Product as Sender;

class ProductImportBunchSaveAfter implements ObserverInterface
{
    const EVENT = 'catalog_product_import_bunch_save_after';

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

    public function __construct(
        Synchronization $synchronizationHelper,
        Tracking $trackingHelper,
        Publisher $publisher
    ) {
        $this->synchronizationHelper = $synchronizationHelper;
        $this->trackingHelper = $trackingHelper;
        $this->publisher = $publisher;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->trackingHelper->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        $productsByStore = [];
        $bunch = $observer->getEvent()->getData('bunch');
        foreach ($bunch as $product) {
            if (isset($product['entity_id']) && isset($product['store_id'])) {
                $productsByStore[$product['store_id']][] = $product['entity_id'];
            }
        }

        $enabledStoreIds = $this->synchronizationHelper->getEnabledStores();
        foreach ($productsByStore as $storeId => $entityIds) {
            if (in_array($storeId, $enabledStoreIds)) {
                $this->publisher->schedule(Sender::MODEL, $entityIds, $storeId);
            }
        }
    }
}
