<?php

namespace Synerise\Integration\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use \Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\Model\Synchronization\MessageQueue\Data\Single\Publisher;
use Synerise\Integration\Model\Synchronization\Sender\Product as Sender;

class CatalogProductSaveAfter implements ObserverInterface
{
    const EVENT = 'catalog_product_save_after';

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

    public function execute(Observer $observer)
    {
        if (!$this->trackingHelper->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        try {
            $this->publishForEachStore($observer->getEvent()->getProduct());
        } catch (\Exception $e) {
            $this->trackingHelper->getLogger()->error($e);
        }
    }

    protected function publishForEachStore(Product $product)
    {
        $enabledStores = $this->synchronizationHelper->getEnabledStores();
        $storeIds = $product->getStoreIds();
        foreach ($storeIds as $storeId) {
            if (in_array($storeId, $enabledStores)) {
                $this->publisher->publish(
                    Sender::MODEL,
                    $product->getEntityId(),
                    $product->getStoreId(),
                    $this->synchronizationHelper->getWebsiteIdByStoreId($product->getStoreId())
                );
            }
        }
    }
}
