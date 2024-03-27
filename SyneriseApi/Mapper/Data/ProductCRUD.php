<?php

namespace Synerise\Integration\SyneriseApi\Mapper\Data;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Model\StockRegistry;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\CatalogsApiClient\Model\AddItem;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Product\Category;
use Synerise\Integration\Helper\Product\Image;
use Synerise\Integration\Helper\Product\Price;
use Synerise\Integration\Model\Config\Source\Debug\Exclude;
use Synerise\Integration\Model\Config\Source\Products\Attributes;

class ProductCRUD
{
    public const XML_PATH_PRODUCTS_LABELS_ENABLED = 'synerise/product/labels_enabled';

    /**
     * @var string|string[]
     */
    protected $attributes = [];

    /**
     * @var array
     */
    protected $parentData = [];

    /**
     * @var string[]
     */
    protected $storeUrls = [];

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Configurable
     */
    protected $configurable;

    /**
     * @var StockRegistry
     */
    protected $stockRegistry;

    /**
     * @var Category
     */
    protected $categoryHelper;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var Price
     */
    protected $priceHelper;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param Configurable $configurable
     * @param StockRegistry $stockRegistry
     * @param Category $categoryHelper
     * @param Image $imageHelper
     * @param Logger $loggerHelper
     * @param Price $priceHelper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        Configurable $configurable,
        StockRegistry $stockRegistry,
        Category $categoryHelper,
        Image $imageHelper,
        Logger $loggerHelper,
        Price $priceHelper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->configurable = $configurable;
        $this->stockRegistry = $stockRegistry;
        $this->categoryHelper = $categoryHelper;
        $this->imageHelper = $imageHelper;
        $this->loggerHelper = $loggerHelper;
        $this->priceHelper = $priceHelper;
    }

    /**
     * Prepare item request
     *
     * @param Product $product
     * @param int $websiteId
     * @param int $delete
     * @return AddItem
     * @throws NoSuchEntityException
     */
    public function prepareRequest(Product $product, int $websiteId, $delete = 0): AddItem
    {
        $value = $this->getTypeSpecificData($product);
        $value['itemId'] = $product->getSku();
        $value['deleted'] = $delete;

        foreach ($this->getAttributesToSelect($product->getStoreId()) as $attributeCode) {
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
        if ($product->getPrice()) {
            $value['price'] = $this->priceHelper->getPrice($product, $product->getPrice(), $product->getStoreId());
        }
        $value['storeId'] = $product->getStoreId();
        $value['storeUrl'] = $this->getStoreBaseUrl($product->getStoreId());

        $categoryIds = $product->getCategoryIds();
        if ($categoryIds) {
            $value['category'] = $this->categoryHelper->getFormattedCategoryPath(array_shift($categoryIds));
        }

        if ($categoryIds) {
            foreach ($categoryIds as $categoryId) {
                $value['additionalCategories'][] = $this->categoryHelper->getFormattedCategoryPath($categoryId);
            }
        }

        if ($product->getImage()) {
            $value['image'] = $this->imageHelper->getOriginalImageUrl($product->getImage());
        }

        $stockStatus = $this->getStockStatus($product->getSku(), $websiteId);
        $value['stock_status'] = $stockStatus['is_in_stock'];
        $value['is_salable'] = $product->getIsSalable();

        return new AddItem([
            'item_key' => $value['itemId'],
            'value' => $value
        ]);
    }

    /**
     * Get type specific data
     *
     * @param Product $product
     * @return array
     */
    public function getTypeSpecificData(Product $product): array
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
     * Get stock status
     *
     * @param string $sku
     * @param int $websiteId
     * @return StockItemInterface|null
     */
    public function getStockStatus(string $sku, int $websiteId): ?StockItemInterface
    {
        $stockData = null;
        try {
            $stockStatus = $this->stockRegistry->getStockStatusBySku(
                $sku,
                $websiteId
            );

            $stockData = $stockStatus->getStockItem();
        } catch (\Exception $exception) {
            $this->loggerHelper->error($exception);
        }
        return $stockData;
    }

    /**
     * Get product by ID
     *
     * @param int $productId
     * @param int $storeId
     * @return ProductInterface|null
     */
    public function getProductById(int $productId, int $storeId): ?ProductInterface
    {
        try {
            return $this->productRepository->getById($productId, false, $storeId);
        } catch (NoSuchEntityException $exception) {
            if ($this->loggerHelper->isExcludedFromLogging(Exclude::EXCEPTION_PRODUCT_NOT_FOUND)) {
                $this->loggerHelper->warning($exception->getMessage());
            }
        }

        return null;
    }

    /**
     * Get store base URL
     *
     * @param int $storeId
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getStoreBaseUrl(int $storeId): ?string
    {
        if (!isset($this->storeUrls[$storeId])) {
            $store = $this->storeManager->getStore($storeId);
            $this->storeUrls[ $storeId] = $store ? $store->getBaseUrl() : null;
        }
        return $this->storeUrls[$storeId];
    }

    /**
     * Check if attribute labels are enabled
     *
     * @return bool
     */
    public function isAttributeLabelEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PRODUCTS_LABELS_ENABLED
        );
    }

    /**
     * @inheritDoc
     */
    public function getAttributesToSelect(int $storeId): array
    {
        if (!isset($this->attributes[$storeId])) {
            $this->attributes[$storeId] = array_merge($this->getEnabledAttributes($storeId), Attributes::REQUIRED);
        }
        return $this->attributes[$storeId];
    }

    /**
     * Get enabled attributes
     *
     * @param int|null $storeId
     * @return string[]
     */
    public function getEnabledAttributes(?int $storeId = null): array
    {
        $attributes = $this->scopeConfig->getValue(
            Attributes::XML_PATH_PRODUCT_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $attributes ? explode(',', $attributes) : [];
    }
}
