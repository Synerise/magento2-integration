<?php

namespace Synerise\Integration\SyneriseApi\Sender\Data;

use InvalidArgumentException;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Model\StockRegistry;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\CatalogsApiClient\Api\ItemsApi;
use Synerise\CatalogsApiClient\ApiException as CatalogsApiException;
use Synerise\CatalogsApiClient\Model\AddItem;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Product\Category;
use Synerise\Integration\Helper\Product\Image;
use Synerise\Integration\SyneriseApi\Catalogs\Config;
use Synerise\Integration\SyneriseApi\Sender\AbstractSender;
use Synerise\Integration\Model\Config\Source\Debug\Exclude;
use Synerise\Integration\Model\Config\Source\Products\Attributes;
use Synerise\Integration\SyneriseApi\ConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory;

class Product extends AbstractSender implements SenderInterface
{
    public const MODEL = 'product';

    public const ENTITY_ID = 'entity_id';

    public const API_EXCEPTION = CatalogsApiException::class;

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
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var StockRegistry
     */
    protected $stockRegistry;

    /**
     * @var Data
     */
    protected $taxHelper;

    /**
     * @var Config
     */
    protected $catalogsConfig;

    /**
     * @var Category
     */
    protected $categoryHelper;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var IsProductSalableInterface|null
     */
    protected $isProductSalable;

    /**
     * @param ConfigFactory $configFactory
     * @param InstanceFactory $apiInstanceFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param Configurable $configurable
     * @param ResourceConnection $resource
     * @param Data $taxHelper
     * @param StockRegistry $stockRegistry
     * @param Config $catalogsConfig
     * @param Category $categoryHelper
     * @param Image $imageHelper
     * @param Logger $loggerHelper
     * @param IsProductSalableInterface|null $isProductSalable
     */
    public function __construct(
        ConfigFactory $configFactory,
        InstanceFactory $apiInstanceFactory,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        Configurable $configurable,
        ResourceConnection $resource,
        Data $taxHelper,
        StockRegistry $stockRegistry,
        Config $catalogsConfig,
        Category $categoryHelper,
        Image $imageHelper,
        Logger $loggerHelper,
        ?IsProductSalableInterface $isProductSalable = null
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->configurable = $configurable;
        $this->connection = $resource->getConnection();
        $this->stockRegistry = $stockRegistry;
        $this->taxHelper = $taxHelper;
        $this->catalogsConfig = $catalogsConfig;
        $this->categoryHelper = $categoryHelper;
        $this->imageHelper = $imageHelper;
        $this->isProductSalable = $isProductSalable;

        parent::__construct($loggerHelper, $configFactory, $apiInstanceFactory);
    }

    /**
     * Send items
     *
     * @param Collection $collection
     * @param int $storeId
     * @param int|null $websiteId
     * @return void
     * @throws CatalogsApiException
     * @throws NoSuchEntityException|ValidatorException|ApiException
     */
    public function sendItems($collection, int $storeId, ?int $websiteId = null)
    {
        if (!$collection->getSize()) {
            return;
        }

        if (!$websiteId) {
            throw new InvalidArgumentException('Website id required for Product');
        }

        $addItemRequest = [];
        $ids = [];

        /** @var $product \Magento\Catalog\Model\Product */
        foreach ($collection as $product) {
            $ids[] = $product->getEntityId();
            $addItemRequest[] = $this->prepareItemRequest($product, $websiteId);
        }

        $this->addItemsBatchWithInvalidCatalogIdCatch($addItemRequest, $storeId);
        $this->markItemsAsSent($ids, $storeId);
    }

    /**
     * Delete items
     *
     * @param mixed $payload
     * @param int $storeId
     * @param int|null $entityId
     * @return void
     * @throws CatalogsApiException|ValidatorException|ApiException
     */
    public function deleteItem($payload, int $storeId, ?int $entityId = null)
    {
        $this->addItemsBatchWithInvalidCatalogIdCatch([$payload], $storeId);
        if ($entityId) {
            $this->deleteStatus([$entityId], $storeId);
        }
    }

    /**
     * Add items batch with catalog ID catch
     *
     * @param mixed $addItemRequest
     * @param int $storeId
     * @return void
     * @throws CatalogsApiException
     * @throws ValidatorException
     * @throws ApiException
     */
    public function addItemsBatchWithInvalidCatalogIdCatch($addItemRequest, int $storeId)
    {
        try {
            $this->addItemsBatch(
                $storeId,
                $this->catalogsConfig->getCatalogId($storeId),
                $addItemRequest
            );
        } catch (CatalogsApiException $e) {
            if ($e->getCode() == 404 || ($e->getCode() == 403 && $this->isStoreForbiddenException($e))) {
                $this->catalogsConfig->resetByScopeId($storeId);
                $this->addItemsBatch(
                    $storeId,
                    $this->catalogsConfig->getCatalogId($storeId),
                    $addItemRequest
                );
            } else {
                throw $e;
            }
        }
    }

    /**
     * Add items batch
     *
     * @param int $storeId
     * @param int $catalogId
     * @param mixed $payload
     * @return void
     * @throws CatalogsApiException
     * @throws ValidatorException
     * @throws ApiException
     */
    public function addItemsBatch(int $storeId, int $catalogId, $payload)
    {
        try {
            list ($body, $statusCode, $headers) = $this->sendWithTokenExpiredCatch(
                function () use ($storeId, $catalogId, $payload) {
                    $this->getItemsApiInstance($storeId)
                        ->addItemsBatchWithHttpInfo($catalogId, $payload);
                },
                $storeId
            );

            if ($statusCode == 207) {
                $this->loggerHelper->getLogger()->warning('Request partially accepted', ['response' => $body]);
            }
        } catch (CatalogsApiException $e) {
            $this->logApiException($e);
            throw $e;
        }
    }

    /**
     * Get Items API instance
     *
     * @param int $storeId
     * @return ItemsApi
     * @throws ApiException
     * @throws ValidatorException
     */
    protected function getItemsApiInstance(int $storeId): ItemsApi
    {
        return $this->getApiInstance('items', $storeId);
    }

    /**
     * Prepare item request
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param int $websiteId
     * @return AddItem
     * @throws NoSuchEntityException
     */
    public function prepareItemRequest(\Magento\Catalog\Model\Product $product, int $websiteId): AddItem
    {
        $value = $this->getTypeSpecificData($product);
        $value['itemId'] = $product->getSku();
        $value['deleted'] = 0;

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

        $value['price'] = $this->taxHelper->getTaxPrice($product, $product->getPrice(), true);
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

        if ($this->isProductSalable) {
            $isSalable = $this->isProductSalable->execute($product->getSku(), $stockStatus->getStockId());
            $value['is_salable'] = (int) ($isSalable && $product->getStatus() == 1 && (int) $value['stock_status']);
        }

        return new AddItem([
            'item_key' => $value['itemId'],
            'value' => $value
        ]);
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

    /**
     * Get type specific data
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function getTypeSpecificData(\Magento\Catalog\Model\Product $product): array
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
            $this->loggerHelper->getLogger()->error($exception);
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
                $this->loggerHelper->getLogger()->warning($exception->getMessage());
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
     * Mark products as sent
     *
     * @param int[] $ids
     * @param int $storeId
     * @return void
     */
    protected function markItemsAsSent(array $ids, int $storeId = 0)
    {
        $data = [];
        foreach ($ids as $id) {
            $data[] = [
                'product_id' => $id,
                'store_id' => $storeId
            ];
        }
        $this->connection->insertOnDuplicate(
            $this->connection->getTableName('synerise_sync_product'),
            $data
        );
    }

    /**
     * Delete status
     *
     * @param int[] $entityIds
     * @param int $storeId
     * @return void
     */
    public function deleteStatus(array $entityIds, int $storeId)
    {
        $this->connection->delete(
            $this->connection->getTableName('synerise_sync_product'),
            [
                'store_id = ?' => $storeId,
                'product_id IN (?)' => $entityIds,
            ]
        );
    }

    /**
     * Check if exception indicates forbidden access to specified store ID
     *
     * @param CatalogsApiException $e
     * @return false|int
     */
    protected function isStoreForbiddenException(CatalogsApiException $e)
    {
        return strpos($e->getResponseBody(), 'Some(Id');
    }
}
