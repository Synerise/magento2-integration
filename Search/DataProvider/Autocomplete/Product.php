<?php

namespace Synerise\Integration\Search\DataProvider\Autocomplete;

use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Framework\Pricing\Render;
use Magento\Search\Model\Autocomplete\DataProviderInterface;
use Magento\Search\Model\Autocomplete\ItemFactory;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Search\Container\Autocomplete;
use Synerise\Integration\Search\DataProvider\Autocomplete\Product\PriceRendererResolver;

class Product implements DataProviderInterface
{
    protected $defaultSelectedAttributes = [
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
     * @var Autocomplete
     */
    protected $autocomplete;

    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var PriceRendererResolver
     */
    protected $priceRendererResolver;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var ItemFactory
     */
    protected $itemFactory;

    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(
        Autocomplete $autocomplete,
        CollectionFactory $productCollectionFactory,
        PriceRendererResolver $priceRendererResolver,
        Image $imageHelper,
        ItemFactory $itemFactory,
        Logger $logger
    ) {
        $this->autocomplete = $autocomplete;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->priceRendererResolver = $priceRendererResolver;
        $this->imageHelper = $imageHelper;
        $this->itemFactory = $itemFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function getItems()
    {
        $result = [];
        try {
            if ($this->shouldIncludeProducts() && $response = $this->autocomplete->search()) {
                $ids = [];
                foreach ($response->getData() as $key => $item) {
                    if (isset($item['entity_id'])) {
                        $ids[$item['entity_id']] = $key;
                    }
                }

                $collection = $this->productCollectionFactory->create()
                    ->addIdFilter(array_keys($ids))
                    ->addAttributeToSelect(
                        $this->defaultSelectedAttributes
                    );

                foreach ($collection as $product) {
                    $key = $ids[$product->getEntityId()];
                    $result[$key] = $this->itemFactory->create([
                        'type' => 'product',
                        'title' => $product->getName(),
                        'image' => $this->getImageUrl($product),
                        'url' => $product->getProductUrl(),
                        'price' => $this->renderProductPrice($product, FinalPrice::PRICE_CODE)
                    ]);
                }

            }
        } catch (\Exception $e) {
            $this->logger->debug($e);
        }

        ksort($result);
        return $result;
    }

    protected function shouldIncludeProducts()
    {
        return true;
    }

    /**
     * Get image url
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    protected function getImageUrl(\Magento\Catalog\Model\Product $product): string
    {
        return $this->imageHelper->init($product, 'mini_cart_product_thumbnail')->getUrl();
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param string $priceCode
     * @return string|null
     */
    protected function renderProductPrice(\Magento\Catalog\Model\Product $product, string $priceCode)
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