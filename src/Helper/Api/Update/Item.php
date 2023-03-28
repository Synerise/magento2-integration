<?php

namespace Synerise\Integration\Helper\Api\Update;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Synerise\CatalogsApiClient\Model\AddItem;
use Synerise\Integration\Helper\Api\Context;
use Synerise\Integration\Helper\Api\Update\Item\Category;
use Synerise\Integration\Helper\Api\Update\Item\Image;
use Synerise\Integration\Helper\Api\Update\Item\Stock;
use Synerise\Integration\Model\Config\Source\Products\Attributes;

class Item
{
    const XML_PATH_CATALOG_ID = 'synerise/catalog/id';

    const XML_PATH_PRODUCTS_ATTRIBUTES = 'synerise/product/attributes';

    const XML_PATH_PRODUCTS_LABELS_ENABLED = 'synerise/product/labels_enabled';

    protected $parentData = [];

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Configurable
     */
    protected $configurable;

    /**
     * @var Category
     */
    protected $category;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Image
     */
    protected $image;

    /**
     * @var Stock
     */
    protected $stock;

    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        ProductRepositoryInterface $productRepository,
        Configurable $configurable,
        Category $category,
        Context $context,
        Image $image,
        Stock $stock
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->productRepository = $productRepository;
        $this->configurable = $configurable;
        $this->category = $category;
        $this->context = $context;
        $this->image = $image;
        $this->stock = $stock;
    }

    /**
     * @param string $storeId
     * @return int|null
     */
    public function getCatalogIdFromConfig(string $storeId): ?int
    {
        $catalogId = $this->scopeConfig->getValue(
            self::XML_PATH_CATALOG_ID,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $catalogId != '' ? $catalogId : null;
    }

    /**
     * @param $productId
     * @param $storeId
     * @return ProductInterface|null
     */
    public function getProductById($productId, $storeId): ?ProductInterface
    {
        try {
            return $this->productRepository->getById($productId, false, $storeId);
        } catch (NoSuchEntityException $exception) {
            $this->logger->error("Product Id not found", [$exception]);
        }

        return null;
    }

    /**
     * @param Product $product
     * @param array $attributes
     * @param int|null $websiteId
     * @return AddItem
     * @throws Exception
     */
    public function prepareItemRequest(Product $product, array $attributes, ?int $websiteId = null): AddItem
    {
        if (!$websiteId) {
            $websiteId = $this->context->getWebsiteIdByStoreId($product->getStoreId());
        }

        $value = $this->getTypeSpecificData($product);
        $value['itemId'] = $product->getSku();
        $value['price'] = $product->getPrice();
        $value['deleted'] = 0;

        foreach ($attributes as $attributeCode) {
            if ($this->isAttributeLabelEnabled()) {
                $attributeText = $product->getAttributeText($attributeCode);
                $productValue = $attributeText !== false ? $attributeText : $product->getData($attributeCode);
            } else {
                $productValue = $product->getData($attributeCode);
            }

            if ($productValue !== null && $productValue !== false) {
                $value[$attributeCode] = $productValue;
            }
        }

        $value['storeId'] = $product->getStoreId();
        $value['storeUrl'] = $this->context->getStoreBaseUrl($product->getStoreId());

        $categoryIds = $product->getCategoryIds();
        if ($categoryIds) {
            $value['category'] = $this->category->getFormattedCategoryPath(array_shift($categoryIds));
        }

        if ($categoryIds) {
            foreach ($categoryIds as $categoryId) {
                $value['additionalCategories'][] = $this->category->getFormattedCategoryPath($categoryId);
            }
        }

        if ($product->getImage()) {
            $value['image'] = $this->image->getOriginalImageUrl($product->getImage());
        }

        $stockStatus = $this->stock->getStockStatus($product->getSku(), $websiteId);
        $value['stock_status'] = $stockStatus ? $stockStatus['is_in_stock'] : 0;
        $value['is_salable'] = $stockStatus ? $this->stock->isSalable($product, $stockStatus) : 0;

        return new AddItem([
            'item_key' => $value['itemId'],
            'value' => $value
        ]);
    }

    /**
     * @param int|null $storeId
     * @return string[]
     */
    public function getProductAttributesToSelect(?int $storeId = null): array
    {
        $attributes = $this->getProductAttributes($storeId);
        return array_merge(
            $attributes,
            Attributes::REQUIRED
        );
    }

    /**
     * @param int|null $storeId
     * @return string[]
     */
    protected function getProductAttributes(?int $storeId = null): array
    {
        $attributes = $this->scopeConfig->getValue(
            self::XML_PATH_PRODUCTS_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $attributes ? explode(',', $attributes) : [];
    }

    /**
     * @param Product $product
     * @return array
     * @throws Exception
     */
    protected function getTypeSpecificData(Product $product): array
    {
        if ($product->getVisibility() == Visibility::VISIBILITY_NOT_VISIBLE) {
            $parentIds = $this->configurable->getParentIdsByChild($product->getId());
            if (!isset($parentIds[0])) {
                return [];
            }

            if (isset($this->parentData[$parentIds[0]])) {
                return $this->parentData[$parentIds[0]];
            }

            $parent = $this->getProductById($parentIds[0], $product->getStoreId());
            if (!$parent) {
                return [];
            }

            $this->parentData[$parentIds[0]] = [
                'parentId' => $parent->getSku(),
                'productUrl' => $parent->getUrlInStore()
            ];

            return $this->parentData[$parentIds[0]];
        } else {
            $productUrl = $product->getUrlInStore();

            if ($product->getTypeId() == Configurable::TYPE_CODE) {
                $this->parentData[$product->getId()] = [
                    'parentId' => $product->getSku(),
                    'productUrl' => $productUrl
                ];
            }

            return ['productUrl' => $productUrl];
        }
    }

    /**
     * @return bool
     */
    protected function isAttributeLabelEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PRODUCTS_LABELS_ENABLED
        );
    }
}
