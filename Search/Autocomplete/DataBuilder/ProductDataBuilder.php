<?php

namespace Synerise\Integration\Search\Autocomplete\DataBuilder;

use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Framework\Pricing\Render;

class ProductDataBuilder
{
    public const DEFAULT_ATTRIBUTES = [
        'name',
        'thumbnail',
        'price',
        'special_price',
        'special_from_date',
        'special_to_date',
        'price_type',
        'tax_class_id'
    ];

    /**
     * @var ProductPriceRendererResolver
     */
    protected $priceRendererResolver;

    /**
     * @var Image
     */
    protected $imageHelper;

    public function __construct(
        ProductPriceRendererResolver $priceRendererResolver,
        Image $imageHelper
    ) {
        $this->priceRendererResolver = $priceRendererResolver;
        $this->imageHelper = $imageHelper;
    }

    /**
     * @param Product $product
     * @param int $position
     * @param string|null $correlationId
     * @return array
     */
    public function get(Product $product, int $position, ?string $correlationId = null)
    {
        return [
            'type' => 'product',
            'title' => $product->getName(),
            'image' => $this->getImageUrl($product),
            'url' => $product->getProductUrl(),
            'price' => $this->renderProductPrice($product, FinalPrice::PRICE_CODE),
            'clickData' => json_encode([
                'correlation_id' => $correlationId,
                'item' => $product->getSku(),
                'position' => $position,
                'searchType' => "autocomplete"
            ])
        ];
    }

    /**
     * Get image url
     *
     * @param Product $product
     * @return string
     */
    protected function getImageUrl(Product $product): string
    {
        return $this->imageHelper->init($product, 'mini_cart_product_thumbnail')->getUrl();
    }

    /**
     * @param Product $product
     * @param string $priceCode
     * @return string|null
     */
    protected function renderProductPrice(Product $product, string $priceCode)
    {
        $price = $product->getData($priceCode);
        if ($priceRender = $this->priceRendererResolver->get()) {
            $price = $priceRender->render(
                $priceCode,
                $product,
                [
                    'include_container' => false,
                    'display_minimal_price' => true,
                    'zone' => Render::ZONE_ITEM_LIST,
                    'list_category_page' => true,
                ]
            );
        }

        return $price;
    }
}