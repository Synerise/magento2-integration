<?php

namespace Synerise\Integration\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Model\Synchronization\Product as SyncProduct;

class CatalogProductSaveAfter  extends AbstractObserver implements ObserverInterface
{
    const EVENT = 'catalog_product_save_after';

    /**
     * @var SyncProduct
     */
    private $syncProduct;


    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        SyncProduct $syncProduct
    ) {
        $this->syncProduct = $syncProduct;

        parent::__construct($scopeConfig, $logger);
    }

    public function execute(Observer $observer)
    {
        if (!$this->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();

        try {
            $this->syncProduct->addItemsToQueue([$product]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to add product to cron queue', ['exception' => $e]);
        }
    }
}
