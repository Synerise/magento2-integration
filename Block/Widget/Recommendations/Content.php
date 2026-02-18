<?php

namespace Synerise\Integration\Block\Widget\Recommendations;


use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogWidget\Block\Product\ProductsList;
use Magento\Framework\Pricing\Render;

class Content extends ProductsList
{
    /**
     * @inheritDoc
     */
    public function createCollection(): ?Collection
    {
        return $this->getProductCollection();
    }

    /**
     * @inheritDoc
     */
    public function getProductPriceHtml(
        Product $product,
        $priceType = null,
        $renderZone = Render::ZONE_ITEM_LIST,
        array $arguments = []
    ) {
        $this->getLayout()->unsetElement('product.price.render.default');
        return parent::getProductPriceHtml($product, $priceType, $renderZone, $arguments);
    }

    /**
     * Product collection set by parent block.
     *
     * @return Collection|null
     */
    public function getProductCollection(): ?Collection
    {
        return $this->getData('product_collection');
    }
}