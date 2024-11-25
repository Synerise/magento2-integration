<?php

namespace Synerise\Integration\Plugin\ElasticsuiteCatalog;

use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\App\Request\Http;
use Magento\Swatches\Helper\Data;

if (!class_exists('Smile\ElasticsuiteSwatches\Model\Plugin\ProductImage')) {
    class ProductImage {}
} else {
    class ProductImage extends \Smile\ElasticsuiteSwatches\Model\Plugin\ProductImage
    {
        /**
         * @var Resolver
         */
        protected $layerResolver;

        public function __construct(
            Data $swatchesHelperData,
            Config $eavConfig,
            Http $request,
            Resolver $layerResolver
        ) {
            $this->layerResolver = $layerResolver;
            parent::__construct($swatchesHelperData, $eavConfig, $request);
        }

        /**
         * {@inheritdoc}
         */
        public function beforeGetImage(
            AbstractProduct $subject,
            Product $product,
                            $location,
            array $attributes = []
        ) {
            if ($this->layerResolver->get()->getSearchEngine() == 'elasticsuite') {
                return parent::beforeGetImage($subject, $product, $location, $attributes);
            }

            if ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE
                && ($location == self::CATEGORY_PAGE_GRID_LOCATION || $location == self::CATEGORY_PAGE_LIST_LOCATION)) {
                $request = $this->request->getParams();
                if (is_array($request)) {
                    $filterArray = $this->getFilterArray($request, $product);
                    if (!empty($filterArray)) {
                        $product = $this->loadSimpleVariation($product, $filterArray);
                    }
                }
            }

            return [$product, $location, $attributes];
        }

        /**
         * Get filters from request and replace labels by option ids.
         *
         * @param array $request Request parameters.
         * @param Product $product Product.
         *
         * @return array
         */
        private function getFilterArray(array $request, Product $product)
        {
            $filterArray = [];
            $attributes = $this->eavConfig->getEntityAttributes(Product::ENTITY, $product);

            foreach ($request as $code => $value) {
                if (array_key_exists($code, $attributes)) {
                    $attribute = $attributes[$code];
                    if ($this->canReplaceImageWithSwatch($attribute)) {
                        $filterArray[$code] = $value;
                    }
                }
            }

            return $filterArray;
        }


        /**
         * Load simple product variation of a given configurable product with swatches.
         * (copy/paste of parent method).
         *
         * @param Product $parentProduct Parent configurable product.
         * @param array   $filterArray   Swatch attributes values.
         *
         * @return bool|Product
         */
        private function loadSimpleVariation(Product $parentProduct, array $filterArray)
        {
            $childProduct = $this->swatchHelperData->loadVariationByFallback($parentProduct, $filterArray);
            if ($childProduct && !$childProduct->getImage()) {
                $childProduct = $this->swatchHelperData->loadFirstVariationWithImage($parentProduct, $filterArray);
            }
            if (!$childProduct) {
                $childProduct = $parentProduct;
            }

            return $childProduct;
        }

        /**
         * Check if we can replace original image with swatch image on catalog/category/list page
         * (copy/paste of parent method).
         *
         * @param Attribute $attribute Swatch attribute.
         *
         * @return bool
         */
        private function canReplaceImageWithSwatch($attribute)
        {
            $result = true;
            if (!$this->swatchHelperData->isSwatchAttribute($attribute)) {
                $result = false;
            }

            if (!$attribute->getUsedInProductListing()
                || !$attribute->getIsFilterable()
                || !$attribute->getData('update_product_preview_image')
            ) {
                $result = false;
            }

            return $result;
        }
    }
}
