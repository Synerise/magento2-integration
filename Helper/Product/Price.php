<?php

namespace Synerise\Integration\Helper\Product;

use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Model\ScopeInterface;

class Price
{
    public const XML_PATH_PRODUCT_CALCULATE_TAX = 'synerise/product/calculate_tax';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Data $helper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
    }

    /**
     * Get product price including tax if enabled by config
     *
     * @param Product $product
     * @param float $price
     * @param int|null $storeId
     * @param bool|null $includingTax
     * @return float
     */
    public function getPrice(Product $product, float $price, ?int $storeId = null, ?bool $includingTax = null): float
    {
        if ($includingTax === null) {
            $includingTax = $this->calculateTax($storeId);
        }

        if ($includingTax) {
            return $this->helper->getTaxPrice($product, $price, true);
        } else {
            return $price;
        }
    }

    /**
     * Get final price including tax if enabled by config
     *
     * @param OrderItemInterface $item
     * @param int|null $storeId
     * @return float
     */
    public function getFinalUnitPrice(OrderItemInterface $item, ?int $storeId): float
    {
        if ($this->calculateTax($storeId)) {
            return round(($item->getRowTotal() + $item->getTaxAmount() - $item->getDiscountAmount()) /
                $item->getQtyOrdered(), 2);
        } else {
            return (float) $item->getPrice() - ((float) $item->getDiscountAmount() / $item->getQtyOrdered());
        }
    }

    /**
     * Check if tax calculation is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function calculateTax(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PRODUCT_CALCULATE_TAX,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
