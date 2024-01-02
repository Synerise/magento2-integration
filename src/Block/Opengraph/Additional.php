<?php

namespace Synerise\Integration\Block\Opengraph;

use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Synerise\Integration\Helper\Product\Category;

class Additional extends Template
{
    /**
     * @var Category
     */
    private $categoryHelper;

    /**
     * @param Context $context
     * @param Data $catalogHelper
     * @param Category $categoryHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $catalogHelper,
        Category $categoryHelper,
        array $data = []
    ) {
        $this->product = $catalogHelper->getProduct();
        $this->categoryHelper = $categoryHelper;

        parent::__construct($context, $data);
    }

    /**
     * Retrieve current product model
     *
     * @return Product|null
     */
    protected function getCurrentProduct(): ?Product
    {
        return $this->product;
    }

    /**
     * Get current product price amount
     *
     * @return string|null
     */
    public function getOriginalPriceAmount(): ?string
    {
        $product = $this->getCurrentProduct();
        return $product ? (string) $product
            ->getPriceInfo()
            ->getPrice(RegularPrice::PRICE_CODE)
            ->getAmount() : null;
    }

    /**
     * Get current product categories array in open graph format
     *
     * @return array
     */
    public function getFormattedCategories(): array
    {
        $categories = [];
        $product = $this->getCurrentProduct();
        if ($product) {
            foreach ($product->getCategoryIds() as $categoryId) {
                $categories[] = $this->categoryHelper->getFormattedCategoryPath($categoryId);
            }
        }

        return $categories;
    }

    /**
     * Get current product sku
     *
     * @return string|null
     */
    public function getSku(): ?string
    {
        $product = $this->getCurrentProduct();
        return $product ? $product->getSku() : null;
    }
}
