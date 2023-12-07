<?php

namespace Synerise\Integration\MessageQueue\Sender\Data;

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
use Synerise\CatalogsApiClient\ApiException;
use Synerise\CatalogsApiClient\Model\AddItem;
use Synerise\CatalogsApiClient\Model\Bag;
use Synerise\Integration\Helper\Catalog;
use Synerise\Integration\Helper\Category;
use Synerise\Integration\Helper\Image;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\MessageQueue\Sender\AbstractSender;
use Synerise\Integration\Model\Config\Source\Debug\Exclude;
use Synerise\Integration\Model\Config\Source\Products\Attributes;
use Synerise\Integration\SyneriseApi\ConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory;

class Product extends AbstractSender implements SenderInterface
{
    const MODEL = 'product';
    const ENTITY_ID = 'entity_id';

    const MAX_PAGE_SIZE = 500;

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
     * @throws \Synerise\ApiClient\ApiException
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
     * @param $addItemRequest
     * @param $storeId
     * @return void
     * @throws ApiException
     * @throws ValidatorException
     * @throws \Synerise\ApiClient\ApiException
     */
    public function addItemsBatchWithCatalogCheck($addItemRequest, $storeId)
    {
        $catalogId = $this->getOrAddCatalog($storeId);

        try {
            $this->addItemsBatch($catalogId, $addItemRequest, $storeId);
        } catch (ApiException $e) {
            if ($e->getCode() === 404) {
                $catalogId = $this->addCatalog($storeId);
                $this->addItemsBatch($catalogId, $addItemRequest, $storeId);
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param $catalogId
     * @param $addItemRequest
     * @param $storeId
     * @param bool $isRetry
     * @return void
     * @throws ApiException
     * @throws ValidatorException
     */
    public function addItemsBatch($catalogId, $addItemRequest, $storeId, $isRetry = false)
    {
        try {
            list ($body, $statusCode, $headers) = $this->getItemsApiInstance($storeId)
                ->addItemsBatchWithHttpInfo($catalogId, $addItemRequest);

            if (substr($statusCode, 0, 1) != 2) {
                throw new ApiException(sprintf('Invalid Status [%d]', $statusCode));
            } elseif ($statusCode == 207) {
                $this->logger->warning('Request partially accepted', ['response' => $body]);
            }
        } catch (ApiException $e) {
            $this->handleApiExceptionAndMaybeUnsetToken($e, ConfigFactory::MODE_SCHEDULE, $storeId);
            if (!$isRetry) {
                $this->addItemsBatch($catalogId, $addItemRequest, $storeId, true);
            }
        }
    }

    /**
     * @param int $storeId
     * @return mixed
     * @throws \Synerise\ApiClient\ApiException
     * @throws ValidatorException
     */
    protected function getItemsApiInstance(int $storeId)
    {
        $config = $this->configFactory->getConfig(ConfigFactory::MODE_SCHEDULE, $storeId);
        return $this->apiInstanceFactory->getApiInstance(
            $config->getScopeKey(),
            'items',
            $config
        );
    }

    /**
     * @param int $storeId
     * @return mixed
     * @throws \Synerise\ApiClient\ApiException
     * @throws ValidatorException
     */
    protected function getCatalogsApiInstance(int $storeId)
    {
        $config = $this->configFactory->getConfig(ConfigFactory::MODE_SCHEDULE, $storeId);
        return $this->apiInstanceFactory->getApiInstance(
            $config->getScopeKey(),
            'catalogs',
            $config
        );
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

        if($this->isProductSalable) {
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
            $this->attributes[$storeId] = array_merge($this->getEnabledAttributes($storeId),Attributes::REQUIRED);
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
     * @param $storeId
     * @param bool $isRetry
     * @return mixed
     * @throws ApiException
     * @throws ValidatorException
     */
    public function addCatalog($storeId, $isRetry = false)
    {
        try {
            $addBagRequest = new \Synerise\CatalogsApiClient\Model\AddBag([
                'name' => $this->catalogHelper->getCatalogNameByStoreId($storeId)
            ]);

            $response = $this->getCatalogsApiInstance($storeId)
                ->addBagWithHttpInfo($addBagRequest);
            $catalogId = $response[0]->getData()->getId();

            $this->catalogHelper->saveConfigCatalogId($catalogId, $storeId);

            return $catalogId;
        } catch (ApiException $e) {
            $this->handleApiExceptionAndMaybeUnsetToken($e, ConfigFactory::MODE_SCHEDULE, $storeId);
            if (!$isRetry) {
                $this->addCatalog($storeId, true);
            }
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
     * @param bool $isRetry
     * @return mixed|Bag|null
     * @throws ApiException
     * @throws ValidatorException
     */
    protected function findExistingCatalogByStoreId($storeId, $isRetry = false)
    {
        try {
        $catalogName = $this->catalogHelper->getCatalogNameByStoreId($storeId);
        $getBagsResponse = $this->getCatalogsApiInstance($storeId)
            ->getBags($catalogName);

        $existingBags = $getBagsResponse->getData();
        foreach ($existingBags as $bag) {
            if ($bag->getName() == $catalogName) {
                return $bag;
            }
        }
    } catch (ApiException $e) {
        $this->handleApiExceptionAndMaybeUnsetToken($e, ConfigFactory::MODE_SCHEDULE, $storeId);
        if (!$isRetry) {
            $this->findExistingCatalogByStoreId($storeId, true);
        }
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
