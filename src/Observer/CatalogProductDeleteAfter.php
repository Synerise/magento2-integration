<?php

namespace Synerise\Integration\Observer;

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

        $product = $observer->getEvent()->getProduct();
        $attributes = $this->catalogHelper->getProductAttributesToSelect();
        $storeId = $this->catalogHelper->getDefaultStoreId();

        try {
            $this->catalogHelper->deleteItemWithCatalogCheck($product, $attributes, $storeId);
        } catch (\Exception $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e]);
        }
    }
}
