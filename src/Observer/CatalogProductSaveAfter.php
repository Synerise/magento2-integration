<?php

namespace Synerise\Integration\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\Model\Synchronization\Product as SyncProduct;

class CatalogProductSaveAfter implements ObserverInterface
{
    const EVENT = 'catalog_product_save_after';

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var SyncProduct
     */
    private $syncProduct;

    public function __construct(
        SyncProduct $syncProduct,
        Tracking $trackingHelper
    ) {
        $this->syncProduct = $syncProduct;
        $this->trackingHelper = $trackingHelper;
    }

    public function execute(Observer $observer)
    {
        if (!$this->trackingHelper->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();

        try {
            $this->syncProduct->addItemsToQueue([$product]);
        } catch (\Exception $e) {
            $this->trackingHelper->getLogger()->error($e);
        }
    }
}
