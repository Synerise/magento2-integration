<?php

namespace Synerise\Integration\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\ObserverInterface;

class CatalogProductDeleteAfter implements ObserverInterface
{
    const EVENT = 'catalog_product_delete_after';

    protected $defaultApi;
    protected $apiHelper;
    protected $catalogHelper;
    protected $trackingHelper;
    protected $logger;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Synerise\Integration\Helper\Api $apiHelper,
        \Synerise\Integration\Helper\Catalog $catalogHelper,
        \Synerise\Integration\Helper\Tracking $trackingHelper
    ) {
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
        $this->catalogHelper = $catalogHelper;
        $this->trackingHelper = $trackingHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->trackingHelper->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();

        try {
            $enabledCatalogStores = $this->catalogHelper->getStoresForCatalogs();
            $productStores = $product->getStoreIds();
            foreach($productStores as $storeId) {
                if(in_array($storeId, $enabledCatalogStores)) {
                    $this->catalogHelper->deleteItemWithCatalogCheck(
                        $this->catalogHelper->getProductById($product->getId(), $storeId),
                        $this->catalogHelper->getProductAttributesToSelect($product->getStoreId())
                    );
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e]);
        }
    }
}
