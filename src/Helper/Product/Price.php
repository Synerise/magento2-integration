<?php

namespace Synerise\Integration\Helper\Product;

use Magento\Catalog\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Model\ScopeInterface;

class Price
{
    const XML_PATH_PRODUCT_CALCULATE_TAX = 'synerise/product/calculate_tax';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Data
     */
    private $helper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Data $helper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
    }

    public function getProductPrice($product, $price, $storeId = null, $includingTax = null)
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

    public function calculateTax($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PRODUCT_CALCULATE_TAX,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getFinalUnitPrice(OrderItemInterface $item, ?int $storeId): float
    {
        if($this->calculateTax($storeId)) {
            return round(($item->getRowTotal() + $item->getTaxAmount() - $item->getDiscountAmount()) /
                $item->getQtyOrdered(), 2);
        } else {
            return (float) $item->getPrice() - ((float) $item->getDiscountAmount() / $item->getQtyOrdered());
        }
    }
}