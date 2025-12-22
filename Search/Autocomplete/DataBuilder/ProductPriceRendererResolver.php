<?php

namespace Synerise\Integration\Search\Autocomplete\DataBuilder;

use Magento\Framework\Pricing\Render;
use Magento\Framework\View\LayoutInterface;

class ProductPriceRendererResolver
{
    /**
     * @var LayoutInterface
     */
    protected $layout;

    public function __construct(
        LayoutInterface $layout
    ) {
        $this->layout = $layout;
    }

    /**
     * Get price renderer block
     *
     * @return Render|null
     */
    public function get()
    {
        $this->layout->getUpdate()->addHandle('default');
        $priceRenderer = $this->layout->getBlock('product.price.render.default');

        if (!$priceRenderer) {
            $priceRenderer = $this->layout->createBlock(
                'Magento\Framework\Pricing\Render',
                'product.price.render.default',
                ['data' => ['price_render_handle' => 'catalog_product_prices']]
            );
        }

        return $priceRenderer;
    }
}