<?php

namespace Synerise\Integration\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use \Synerise\Integration\Helper\Update\Catalog;

class CatalogProductDeleteBefore  extends AbstractObserver implements ObserverInterface
{
    const EVENT = 'catalog_product_delete_after';

    /**
     * @var Catalog
     */
    protected $catalogHelper;


    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        Catalog $catalogHelper,
    ) {
        $this->catalogHelper = $catalogHelper;

        parent::__construct($scopeConfig, $logger);
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->isEventTrackingEnabled(self::EVENT)) {
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
