<?php

namespace Synerise\Integration\Helper;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogInventory\Model\StockRegistry;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventorySales\Model\AreProductsSalable;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\CatalogsApiClient\Model\AddItem;

class Catalog extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $configWriter;
    protected $cacheManager;
    protected $action;
    protected $dateTime;
    protected $storeToWebsite = [];

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    private $stockRegistry;

    /**
     * @var Api
     */
    protected $apiHelper;

    protected $categoryRepository;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    protected $configurable;

    protected $websiteRepository;

    protected $formattedCategoryPaths = [];

    protected $parentData = [];

    /**
     * @var \Magento\Framework\View\Asset\ContextInterface
     */
    private $assetContext;

    /**
     * @var AreProductsSalableInterface|null
     */
    private $areProductsSalable;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection;

    public function __construct(
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        \Magento\Catalog\Model\ResourceModel\Product\Action $action,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Cache\Manager $cacheManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\View\Asset\ContextInterface $assetContext,
        \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository,
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        StockRegistry $stockRegistry,
        Api $apiHelper
    ) {
        $this->stockRegistry = $stockRegistry;
        $this->storeManager = $storeManager;
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->configurable = $configurable;
        $this->action = $action;
        $this->cacheManager = $cacheManager;
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
        $this->dateTime = $dateTime;
        $this->assetContext = $assetContext;
        $this->websiteRepository = $websiteRepository;
        $this->apiHelper = $apiHelper;
        $this->connection = $resource->getConnection();

        if (class_exists(AreProductsSalable::class)) {
            $this->areProductsSalable = ObjectManager::getInstance()->get(AreProductsSalable::class);;
        } else {
            $this->areProductsSalable = null;
        }

        parent::__construct($context);
    }

    public function getConfigCatalogId(string $storeId)
    {
        return $this->scopeConfig->getValue(
            Config::XML_PATH_CATALOG_ID,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function saveConfigCatalogId($catalogId, $store_id)
    {
        $this->configWriter->save(
            Config::XML_PATH_CATALOG_ID,
            $catalogId,
            ScopeInterface::SCOPE_STORE,
            $store_id
        );
        $this->cacheManager->clean(['config']);
    }

    public function addCatalog($storeId)
    {
        $addBagRequest = new \Synerise\CatalogsApiClient\Model\AddBag([
            'name' => $this->getCatalogNameByStoreId($storeId)
        ]);

        $response = $this->apiHelper->getBagsApiInstance($storeId)
            ->addBagWithHttpInfo($addBagRequest);
        $catalogId = $response[0]->getData()->getId();

        $this->saveConfigCatalogId($catalogId, $storeId);

        return $catalogId;
    }

    public function getOrAddCatalog($storeId)
    {
        $catalogId = $this->getConfigCatalogId($storeId);
        if ($catalogId) {
            return $catalogId;
        }

        $catalog = $this->findExistingCatalogByStoreId($storeId);
        if ($catalog) {
            $catalogId = $catalog->getId();
            $this->saveConfigCatalogId($catalog->getId(), $storeId);
        }

        return $catalogId ?: $this->addCatalog($storeId);
    }

    public function findExistingCatalogByStoreId($storeId)
    {
        $catalogName = $this->getCatalogNameByStoreId($storeId);
        $getBagsResponse = $this->apiHelper->getBagsApiInstance($storeId)
            ->getBags($catalogName);

        $existingBags = $getBagsResponse->getData();
        foreach ($existingBags as $bag) {
            if ($bag->getName() == $catalogName) {
                return $bag;
            }
        }

        return null;
    }

    private function getCatalogNameByStoreId($storeId)
    {
        return 'store_'.$storeId;
    }

    public function addItemsBatchWithCatalogCheck($collection, $attributes, $websiteId, $storeId)
    {
        if (!$collection->getSize()) {
            return;
        }

        if(!$websiteId) {
            $websiteId = $this->getWebsiteIdByStoreId($storeId);
        }

        $addItemRequest = [];
        $ids = [];

        /** @var $product \Magento\Catalog\Model\Product */
        foreach ($collection as $product) {
            $ids[] = $product->getEntityId();
            $addItemRequest[] = $this->prepareItemRequest($product, $attributes, $websiteId);
        }

        $this->sendItemsToSyneriseWithCatalogCheck($addItemRequest, $storeId);
        $this->markItemsAsSent($ids, $storeId);
    }

    /**
     * @param Product $product
     * @param string[] $attributes
     * @throws \Exception
     */
    public function deleteItemWithCatalogCheck($product, $attributes)
    {
        $addItemRequest = $this->prepareItemRequest($product, $attributes);
        $addItemRequest->setValue(array_merge(['deleted' => 1], $addItemRequest->getValue()));
        $this->sendItemsToSyneriseWithCatalogCheck([$addItemRequest], $product->getStoreId());
    }

    /**
     * @param int[] $ids
     * @return void
     * @param int $storeId
     */
    protected function markItemsAsSent(array $ids, $storeId = 0)
    {
        $timestamp = $this->dateTime->gmtDate();
        $data = [];
        foreach ($ids as $id) {
            $data[] = [
                'synerise_updated_at' => $timestamp,
                'product_id' => $id,
                'store_id' => $storeId
            ];
        }
        $this->connection->insertOnDuplicate(
            $this->connection->getTableName('synerise_sync_product'),
            $data
        );
    }

    public function prepareItemRequest($product, $attributes, $websiteId = null)
    {
        $value = $this->getTypeSpecificData($product);
        $value['itemId'] = $product->getSku();
        $value['price'] = $product->getPrice();
        $value['deleted'] = 0;

        foreach ($attributes as $attribute) {
            $productValue = $product->getData($attribute);
            if ($productValue) {
                $value[$attribute] = $productValue;
            }
        }

        $categoryIds = $product->getCategoryIds();
        if ($categoryIds) {
            $value['category'] = $this->getFormattedCategoryPath(array_shift($categoryIds));
        }

        if ($categoryIds) {
            foreach ($categoryIds as $categoryId) {
                $value['additionalCategories'][] = $this->getFormattedCategoryPath($categoryId);
            }
        }

        if ($product->getImage()) {
            $value['image'] = $this->getOriginalImageUrl($product->getImage());
        }

        $stockStatus = $this->getStockStatus($product->getSku(), $websiteId);
        $value['stock_status'] = $stockStatus['is_in_stock'];
        if ($this->areProductsSalable) {
            $productsAreSalableArray = $this->areProductsSalable->execute([$product->getSku()], $stockStatus->getStockId());
            $value['is_salable'] = (int) ($productsAreSalableArray[0]->isSalable() && $product->getStatus() == 1 && (int) $value['stock_status']);
        }

        return new AddItem([
            'item_key' => $value['itemId'],
            'value' => $value
        ]);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     * @throws \Exception
     */
    public function prepareParamsFromQuoteProduct($product)
    {
        $sku = $product->getData('sku');
        $skuVariant = $product->getSku();

        $params = [
            "sku" => $sku,
            "name" => $product->getName(),
            "regularUnitPrice" => [
                "amount" => (float) $product->getPrice(),
                "currency" => $this->getCurrencyCode()
            ],
            "finalUnitPrice" => [
                "amount" => (float) $product->getFinalPrice(),
                "currency" => $this->getCurrencyCode()
            ],
            "productUrl" => $product->getUrlInStore(),
            "quantity" => $product->getQty()
        ];

        if ($sku!= $skuVariant) {
            $params['skuVariant'] = $skuVariant;
        }

        if ($product->getSpecialPrice()) {
            $params['discountedUnitPrice'] = [
                "amount" => (float) $product->getSpecialPrice(),
                "currency" => $this->getCurrencyCode()
            ];
        }

        $categoryIds = $product->getCategoryIds();
        if ($categoryIds) {
            $params['categories'] = [];
            foreach ($categoryIds as $categoryId) {
                $params['categories'][] = $this->getFormattedCategoryPath($categoryId);
            }

            if ($product->getCategoryId()) {
                $category = $this->getFormattedCategoryPath($product->getCategoryId());
                if ($category) {
                    $params['category'] = $category;
                }
            }
        }

        if ($product->getImage()) {
            $params['image'] = $this->getOriginalImageUrl($product->getImage());
        }

        return $params;
    }

    public function prepareProductsFromQuote($quote)
    {
        $products = [];
        $items = $quote->getAllVisibleItems();
        if (is_array($items)) {
            foreach ($items as $item) {
                $products[] = $this->prepareProductFromQuoteItem($item);
            }
        }

        return $products;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     * @throws \Exception
     */
    private function prepareProductFromQuoteItem($item)
    {
        $product = $item->getProduct();

        $sku = $product->getData('sku');
        $skuVariant = $item->getSku();

        $params = [
            "sku" => $sku,
            "quantity" => $item->getQty()
        ];

        if ($sku!= $skuVariant) {
            $params['skuVariant'] = $skuVariant;
        }

        $categoryIds = $product->getCategoryIds();
        if ($categoryIds) {
            $params['categories'] = [];
            foreach ($categoryIds as $categoryId) {
                $params['categories'][] = $this->getFormattedCategoryPath($categoryId);
            }
        }

        return $params;
    }

    public function getCurrencyCode()
    {
        return $this->storeManager->getStore()->getCurrentCurrency()->getCode();
    }

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

    public function getProductById($productId, $storeId)
    {
        try {
            return $this->productRepository->getById($productId, false, $storeId);
        } catch (NoSuchEntityException $exception) {
            $this->_logger->error("Product Id not found", [$exception]);
        }

        return null;
    }

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
            $this->_logger->error($exception->getMessage());
        }
        return $stockData;
    }

    public function sendItemsToSyneriseWithCatalogCheck($addItemRequest, $storeId)
    {
        $catalogId = $this->getOrAddCatalog($storeId);

        try {
            $this->sendItemsToSynerise($catalogId, $addItemRequest, $storeId);
        } catch (\Exception $e) {
            if ($e->getCode() === 404) {
                $catalogId = $this->addCatalog($storeId);
                $this->sendItemsToSynerise($catalogId, $addItemRequest, $storeId);
            } else {
                throw $e;
            }
        }
    }

    public function sendItemsToSynerise($catalogId, $addItemRequest, $storeId)
    {
        list ($body, $statusCode, $headers) = $this->apiHelper->getItemsApiInstance($storeId)
            ->addItemsBatchWithHttpInfo($catalogId, $addItemRequest);

        if (substr($statusCode, 0, 1) != 2) {
            throw new ApiException(sprintf('Invalid Status [%d]', $statusCode));
        } elseif ($statusCode == 207) {
            $this->_logger->debug('Request accepted with errors', ['response' => $body]);
        }
    }

    public function getFormattedCategoryPath($categoryId)
    {
        if (!isset($this->formattedCategoryPaths[$categoryId])) {
            /** @var $category \Magento\Catalog\Model\Category */
            $category = $this->categoryRepository->get($categoryId);

            if ($category->getParentId()) {
                $parentCategoryPath = $this->getFormattedCategoryPath($category->getParentId());
                $this->formattedCategoryPaths[$categoryId] = $parentCategoryPath ?
                    $parentCategoryPath . ' > ' . $category->getName() : $category->getName();
            } else {
                $this->formattedCategoryPaths[$categoryId] = $category->getName();
            }
        }

        return $this->formattedCategoryPaths[$categoryId] ?
            $this->formattedCategoryPaths[$categoryId] : null;
    }

    public function getProductAttributes($storeId = null)
    {
        $attributes = $this->scopeConfig->getValue(
            \Synerise\Integration\Helper\Config::XML_PATH_PRODUCTS_ATTRIBUTES,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $attributes ? explode(',', $attributes) : [];
    }

    public function getProductAttributesToSelect($storeId = null)
    {
        $attributes = $this->getProductAttributes($storeId);
        return array_merge(
            $attributes,
            \Synerise\Integration\Model\Config\Source\Products\Attributes::REQUIRED
        );
    }

    public function getStoresForCatalogs()
    {
        $attributes = $this->scopeConfig->getValue(
            \Synerise\Integration\Helper\Config::XML_PATH_PRODUCTS_STORES
        );

        return $attributes ? explode(',', $attributes) : [];
    }

    /**
     * Get URL to the original version of the product image.
     *
     * @return string|null
     */
    public function getOriginalImageUrl($filePath)
    {
        return $filePath ? $this->assetContext->getBaseUrl() . $filePath : null;
    }

    /**
     * @return int|null
     */
    public function getDefaultStoreId()
    {
        try {
            $website = $this->storeManager->getDefaultStoreView()->getId();
        } catch (LocalizedException $localizedException) {
            $website = null;
            $this->_logger->error($localizedException->getMessage());
        }
        return $website;
    }

    /**
     * Get Website code by store id
     *
     * @param int $storeId
     * @return string|null
     */
    public function getWebsiteIdByStoreId(int $storeId): ?string
    {
        try {
            if(!isset($storeToWebsite[$storeId])) {
                $storeToWebsite[$storeId] = (int) $this->storeManager->getStore($storeId)->getWebsiteId();
            }
            return $storeToWebsite[$storeId];
        } catch (NoSuchEntityException $entityException) {
            $this->_logger->debug('Store not found '.$storeId);
        }

        return null;
    }
}
