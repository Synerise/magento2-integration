<?php

namespace Synerise\Integration\Helper\Product;

use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Model\ScopeInterface;
use Synerise\Integration\Model\Config\Source\Products\Price as PriceCode;

class Price
{
    public const XML_PATH_PRODUCT_INCLUDE_TAX = 'synerise/product/calculate_tax';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Data
     */
    private $catalogHelper;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $catalogHelper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Data $catalogHelper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->catalogHelper = $catalogHelper;
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
    public function getTaxPrice(Product $product, float $price, ?int $storeId = null, ?bool $includingTax = null): float
    {
        if ($includingTax === null) {
            $includingTax = $this->includeTax($storeId);
        }

        return $this->catalogHelper->getTaxPrice(
            $product,
            $price,
            $includingTax,
            null,
            null,
            null,
            $storeId
        );
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
        if ($this->includeTax($storeId)) {
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
    public function includeTax(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PRODUCT_INCLUDE_TAX,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get price code for price attribute
     *
     * @param int|null $storeId
     * @return string
     */
    public function getPriceCode(?int $storeId = null): string
    {
        return $this->scopeConfig->getValue(
            PriceCode::XML_PATH_PRODUCT_PRICE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
