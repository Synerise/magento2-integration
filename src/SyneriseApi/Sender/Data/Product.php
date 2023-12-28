<?php

namespace Synerise\Integration\SyneriseApi\Sender\Data;

use InvalidArgumentException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Visibility;
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
use Psr\Log\LoggerInterface;
use Synerise\CatalogsApiClient\Api\BagsApi;
use Synerise\CatalogsApiClient\Api\ItemsApi;
use Synerise\CatalogsApiClient\ApiException;
use Synerise\CatalogsApiClient\Model\AddBag;
use Synerise\CatalogsApiClient\Model\AddItem;
use Synerise\CatalogsApiClient\Model\Bag;
use Synerise\Integration\Helper\Catalog;
use Synerise\Integration\Helper\Category;
use Synerise\Integration\Helper\Image;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\SyneriseApi\Sender\AbstractSender;
use Synerise\Integration\Model\Config\Source\Debug\Exclude;
use Synerise\Integration\Model\Config\Source\Products\Attributes;
use Synerise\Integration\SyneriseApi\ConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory;

class Product extends AbstractSender implements SenderInterface
{
    const MODEL = 'product';

    const ENTITY_ID = 'entity_id';

    const MAX_PAGE_SIZE = 100;

    const API_EXCEPTION = ApiException::class;


    const XML_PATH_PRODUCTS_LABELS_ENABLED = 'synerise/product/labels_enabled';

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
     * @var LoggerInterface
     */
    protected $logger;

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
     * @var Catalog
     */
    protected $catalogHelper;

    /**
     * @var Category
     */
    protected $categoryHelper;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var IsProductSalableInterface|null
     */
    protected $isProductSalable;

    public function __construct(
        LoggerInterface $logger,
        ConfigFactory $configFactory,
        InstanceFactory $apiInstanceFactory,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        Configurable $configurable,
        ResourceConnection $resource,
        StockRegistry $stockRegistry,
        Catalog $catalogHelper,
        Category $categoryHelper,
        Image $imageHelper,
        Tracking $trackingHelper,
        ?IsProductSalableInterface $isProductSalable = null
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->configurable = $configurable;
        $this->connection = $resource->getConnection();
        $this->stockRegistry = $stockRegistry;
        $this->catalogHelper = $catalogHelper;
        $this->categoryHelper = $categoryHelper;
        $this->imageHelper = $imageHelper;
        $this->trackingHelper = $trackingHelper;
        $this->isProductSalable = $isProductSalable;

        parent::__construct($logger, $configFactory, $apiInstanceFactory);
    }

    /**
     * @param $collection
     * @param int $storeId
     * @param int|null $websiteId
     * @return void
     * @throws ApiException
     * @throws NoSuchEntityException
     * @throws ValidatorException
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

        $this->addItemsBatchWithCatalogCheck($addItemRequest, $storeId);
        $this->markItemsAsSent($ids, $storeId);
    }

    /**
     * @param $payload
     * @param int $storeId
     * @param int|null $entityId
     * @return void
     * @throws ApiException
     * @throws ValidatorException
     */
    public function deleteItem($payload, int $storeId, ?int $entityId = null)
    {
        $this->addItemsBatchWithCatalogCheck([$payload], $storeId);
        if ($entityId) {
            $this->deleteStatus([$entityId], $storeId);
        }
    }

    /**
     * @param $addItemRequest
     * @param $storeId
     * @return void
     * @throws ApiException
     * @throws ValidatorException
     */
    public function addItemsBatchWithCatalogCheck($addItemRequest, $storeId)
    {
        $catalogId = $this->getOrAddCatalog($storeId);

        try {
            $this->addItemsBatch($storeId, $catalogId, $addItemRequest);
        } catch (ApiException $e) {
            if ($e->getCode() === 404) {
                $catalogId = $this->addCatalog($storeId);
                $this->addItemsBatch($storeId, $catalogId, $addItemRequest);
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param int $storeId
     * @param int $catalogId
     * @param $payload
     * @return void
     * @throws ApiException
     * @throws ValidatorException
     * @throws \Synerise\ApiClient\ApiException
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
                $this->logger->warning('Request partially accepted', ['response' => $body]);
            }
        } catch (ApiException $e) {
            $this->logApiException($e);
            throw $e;
        }
    }

    /**
     * @param int $storeId
     * @return ItemsApi
     * @throws \Synerise\ApiClient\ApiException
     * @throws ValidatorException
     */
    protected function getItemsApiInstance(int $storeId): ItemsApi
    {
        return $this->getApiInstance('items', $storeId);
    }

    /**
     * @param int $storeId
     * @return BagsApi
     * @return mixed
     * @throws ValidatorException|\Synerise\ApiClient\ApiException
     */
    protected function getCatalogsApiInstance(int $storeId): BagsApi
    {
        return $this->getApiInstance('catalogs', $storeId);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param int $websiteId
     * @return AddItem
     * @throws NoSuchEntityException
     */
    public function prepareItemRequest(\Magento\Catalog\Model\Product $product, int $websiteId)
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

        $value['price'] = $product->getPrice();
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
     * @return bool
     */
    public function isAttributeLabelEnabled()
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
     * @param $storeId
     * @return string[]
     */
    public function getEnabledAttributes($storeId = null)
    {
        $attributes = $this->scopeConfig->getValue(
            Attributes::XML_PATH_PRODUCT_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $attributes ? explode(',', $attributes) : [];
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return array|mixed
     */
    public function getTypeSpecificData(\Magento\Catalog\Model\Product $product)
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
     * @param $sku
     * @param $websiteId
     * @return \Magento\CatalogInventory\Api\Data\StockItemInterface|null
     */
    public function getStockStatus($sku, $websiteId)
    {
        $stockData = null;
        try {
            $stockStatus = $this->stockRegistry->getStockStatusBySku(
                $sku,
                $websiteId
            );

            $stockData = $stockStatus->getStockItem();
        } catch (\Exception $exception) {
            $this->logger->error($exception);
        }
        return $stockData;
    }

    /**
     * @param $productId
     * @param $storeId
     * @return \Magento\Catalog\Api\Data\ProductInterface|null
     */
    public function getProductById($productId, $storeId)
    {
        try {
            return $this->productRepository->getById($productId, false, $storeId);
        } catch (NoSuchEntityException $exception) {
            if ($this->trackingHelper->isExcludedFromLogging(Exclude::EXCEPTION_PRODUCT_NOT_FOUND)) {
                $this->logger->warning($exception->getMessage());
            }
        }

        return null;
    }

    /**
     * @param $storeId
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getStoreBaseUrl($storeId)
    {
        if (!isset($this->storeUrls[$storeId])) {
            $store = $this->storeManager->getStore($storeId);
            $this->storeUrls[ $storeId] = $store ? $store->getBaseUrl() : null;
        }
        return $this->storeUrls[$storeId];
    }

    /**
     * @param int $storeId
     * @return mixed
     * @throws ApiException
     * @throws ValidatorException
     * @throws \Synerise\ApiClient\ApiException
     */
    public function addCatalog(int $storeId)
    {
        try {
            $response = $this->sendWithTokenExpiredCatch(
                function () use ($storeId) {
                    $this->getCatalogsApiInstance($storeId)
                        ->addBagWithHttpInfo(new AddBag([
                            'name' => $this->catalogHelper->getCatalogNameByStoreId($storeId)
                        ]));
                },
                $storeId
            );

            $catalogId = $response[0]->getData()->getId();
            $this->catalogHelper->saveConfigCatalogId($catalogId, $storeId);

            return $catalogId;
        } catch (ApiException $e) {
            $this->logApiException($e);
            throw $e;
        }
    }

    /**
     * @param $storeId
     * @param $timeout
     * @return mixed|string
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \Synerise\ApiClient\ApiException
     * @throws \Synerise\CatalogsApiClient\ApiException
     */
    public function getOrAddCatalog($storeId, $timeout = null)
    {
        $catalogId = $this->catalogHelper->getConfigCatalogId($storeId);
        if ($catalogId) {
            return $catalogId;
        }

        $catalog = $this->findExistingCatalogByStoreId($storeId);
        if ($catalog) {
            $catalogId = $catalog->getId();
            $this->catalogHelper->saveConfigCatalogId($catalog->getId(), $storeId);
        }

        return $catalogId ?: $this->addCatalog($storeId, $timeout);
    }

    /**
     * @param $storeId
     * @return mixed|Bag|null
     * @throws ApiException
     * @throws ValidatorException
     * @throws \Synerise\ApiClient\ApiException
     */
    protected function findExistingCatalogByStoreId($storeId)
    {
        try {
            $catalogName = $this->catalogHelper->getCatalogNameByStoreId($storeId);
            $getBagsResponse = $this->sendWithTokenExpiredCatch(
                function () use ($storeId) {
                    $this->getCatalogsApiInstance($storeId)->getBags(
                        $this->catalogHelper->getCatalogNameByStoreId($storeId)
                    );
                },
                $storeId
            );

            $existingBags = $getBagsResponse->getData();
            foreach ($existingBags as $bag) {
                if ($bag->getName() == $catalogName) {
                    return $bag;
                }
            }
        } catch (ApiException $e) {
            $this->logApiException($e);
            throw $e;
        }

        return null;
    }


    /**
     * @param int[] $ids
     * @param int $storeId
     * @return void
     */
    protected function markItemsAsSent(array $ids, $storeId = 0)
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
}
