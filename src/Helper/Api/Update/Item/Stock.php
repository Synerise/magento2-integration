<?php

namespace Synerise\Integration\Helper\Api\Update\Item;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Model\StockRegistry;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Psr\Log\LoggerInterface;

class Stock
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var IsProductSalableInterface
     */
    protected $isProductSalable;

    /**
     * @var StockRegistry
     */
    protected $stockRegistry;

    public function __construct(
        LoggerInterface $logger,
        IsProductSalableInterface $isProductSalable,
        StockRegistry $stockRegistry
    ) {
        $this->logger = $logger;
        $this->isProductSalable = $isProductSalable;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * @param string $sku
     * @param int $websiteId
     * @return StockItemInterface|null
     */
    public function getStockStatus(string $sku, int $websiteId): ?StockItemInterface
    {
        $stockData = null;
        try {
            $stockStatus = $this->stockRegistry->getStockStatusBySku(
                $sku,
                $websiteId
            );

            $stockData = $stockStatus->getStockItem();

        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
        return $stockData;
    }

    /**
     * @param Product $product
     * @param StockItemInterface $stockItem
     * @return int
     */
    public function isSalable(Product $product, StockItemInterface $stockItem): int
    {
        $isSalable = $this->isProductSalable->execute($product->getSku(), $stockItem->getStockId());
        return (int) ($isSalable && $product->getStatus() == 1 && (int) $stockItem['is_in_stock']);
    }
}
