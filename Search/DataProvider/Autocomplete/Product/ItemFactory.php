<?php

namespace Synerise\Integration\Search\DataProvider\Autocomplete\Product;

use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Pricing\Render;
use Magento\Search\Model\Autocomplete\Item;

class ItemFactory
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var PriceRendererResolver
     */
    protected $priceRendererResolver;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        PriceRendererResolver $priceRendererResolver,
        Image $imageHelper,

    ) {
        $this->objectManager = $objectManager;
        $this->priceRendererResolver = $priceRendererResolver;
        $this->imageHelper = $imageHelper;
    }

    /**
     * @param Product $product
     * @param int $position
     * @param string|null $correlationId
     * @return Item
     */
    public function create(Product $product, int $position, ?string $correlationId = null)
    {
        $data = [
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

        return $this->objectManager->create(Item::class, ['data' => $data]);
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