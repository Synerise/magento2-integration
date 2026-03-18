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
use Synerise\Integration\Model\Config\Source\Products\Attributes\Format;
use Synerise\Integration\Search\Attributes\Config;

class ProductCRUD
{
    public const XML_PATH_PRODUCT_FILTERABLE_ATTRIBUTES = 'synerise/product/filterable_attributes';

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
     * @var Config
     */
    protected $attributesConfig;

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
     * Array of available price codes
     *
     * @var array
     */
    protected $priceCodes = ['regular_price', 'final_price', 'special_price','tier_price','minimal_price'];

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $attributesConfig
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
        Config $attributesConfig,
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
        $this->attributesConfig = $attributesConfig;
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
    public function prepareRequest(Product $product, int $websiteId, $delete = 0, array $options = []): AddItem
    {
        $value = $this->getTypeSpecificData($product);
        $value['itemId'] = $product->getSku();
        $value['deleted'] = $delete;

        $storeId = $product->getStoreId();
        $value['storeId'] = $storeId;
        $value['storeUrl'] = $this->getStoreBaseUrl($storeId);

        $priceInfo = $product->getPriceInfo();
        $categoryIds = $product->getCategoryIds();

        $configurableAttributes = $this->getConfigurableAttributes($product);
        foreach ($this->getAttributesToSend($storeId) as $attributeCode) {
            if (isset($configurableAttributes[$attributeCode])) {
                $value[$attributeCode] = $configurableAttributes[$attributeCode];
                if (isset($configurableAttributes[$attributeCode . '_oid'])) {
                    $value[$attributeCode . '_oid'] = $configurableAttributes[$attributeCode . '_oid'];
                }
            } elseif ($attributeCode == 'category_ids') {
                if ($categoryIds) {
                    $value['category_ids'] = $this->categoryHelper->getAllCategoryIds($categoryIds);
                }
            } elseif($attributeCode == 'image') {
                if ($product->getImage()) {
                    $value['image'] = $this->imageHelper->getOriginalImageUrl($product->getImage());
                }
            } elseif($attributeCode == 'price') {
                if ($priceInfo && $price = $priceInfo->getPrice($this->priceHelper->getPriceCode($storeId))) {
                    $value['price'] = $this->priceHelper->getTaxPrice($product, $price->getValue(), $storeId);
                }
            } elseif(in_array($attributeCode, $this->priceCodes)) {
                if ($priceInfo && ($price = $priceInfo->getPrice($attributeCode)) && $price->getValue()) {
                    $value[$attributeCode] = $this->priceHelper->getTaxPrice($product, $price->getValue(), $storeId);
                }
            } else {
                $this->formatAttribute($value, $product, $attributeCode);
            }
        }

        if ($categoryIds) {
            $value['category'] = $this->categoryHelper->getFormattedCategoryPath(array_shift($categoryIds));
            foreach ($categoryIds as $categoryId) {
                $value['additionalCategories'][] = $this->categoryHelper->getFormattedCategoryPath($categoryId);
            }
        }

        if (!$delete && $stockStatus = $this->getStockStatus($product->getSku(), $websiteId)) {
            $value['stock_status'] = $stockStatus['is_in_stock'];
            $value['is_salable'] = $product->getIsSalable();
        }

        if (isset($options['type'])) {
            $value['lastUpdateType'] = $options['type'];
        }


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
     * Get configurable attributes
     *
     * @param Product $product
     * @return array
     */
    public function getConfigurableAttributes(Product $product): array
    {
        $attributesToSend = [];
        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            $configurableAttributes = $product->getTypeInstance()->getConfigurableAttributes($product);
            if ($configurableAttributes) {
                /** @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute $attribute */
                foreach ($configurableAttributes as $attribute) {
                    $attributeCode = $attribute->getProductAttribute()->getAttributeCode();
                    foreach ($attribute->getOptions() as $option) {
                        $this->formatOption($attributesToSend, $option, $attributeCode);
                    }
                }
            }
        }
        return $attributesToSend;
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
     * Get attribute to send
     *
     * @param int $storeId
     * @return array
     */
    public function getAttributesToSend(int $storeId): array
    {
        if (!isset($this->attributes[$storeId])) {
            if ($this->includeAllFilterableAttributes($storeId)) {
                $this->attributes[$storeId] = array_unique(
                    array_merge(
                        $this->getEnabledAttributes($storeId),
                        array_keys($this->attributesConfig->getFieldIds()),
                        Attributes::REQUIRED
                    )
                );
            } else {
                $this->attributes[$storeId] = array_merge(
                    $this->getEnabledAttributes($storeId),
                    Attributes::REQUIRED
                );
            }
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
     * Format option
     *
     * @param array $value
     * @param array $option
     * @param string $attributeCode
     * @return void
     */
    protected function formatOption(array &$value, array $option, string $attributeCode)
    {
        if (!isset($value[$attributeCode])) {
            $value[$attributeCode] = [];
        }

        switch ($this->attributesConfig->getFieldFormatId()) {
            case Format::OPTION_ID_AND_LABEL:
                $value[$attributeCode][] = [
                    'id' => $option['value_index'],
                    'label' => $option['label']
                ];
                break;
            case Format::OPTION_ID_AND_LABEL_ATTR:
                if (!isset($value[$attributeCode . '_oid'])) {
                    $value[$attributeCode . '_oid'] = [];
                }
                $value[$attributeCode][] = $option['label'];
                $value[$attributeCode . '_oid'][] = $option['value_index'];
                break;
            case Format::OPTION_LABEL:
                $value[$attributeCode][] = $option['label'];
                break;
            default:
                $value[$attributeCode][] = $option['value_index'];
        }
    }

    /**
     * Format attribute
     *
     * @param array $value
     * @param Product $product
     * @param string $attributeCode
     * @return void
     */
    protected function formatAttribute(array &$value, Product $product, string $attributeCode)
    {
        $attribute = $product->getResource()->getAttribute($attributeCode);
        if (!$attribute) {
            return;
        }

        $frontendInput = $attribute->getFrontendInput();

        $id = $product->getData($attributeCode);
        if ($id === null || !in_array($frontendInput, ['select', 'multiselect', 'boolean'])) {
            $value[$attributeCode] = $id;
            return;
        }

        if ($frontendInput == 'multiselect') {
            $id = explode(',', $id);
        }

        switch ($this->attributesConfig->getFieldFormatId()) {
            case Format::OPTION_ID_AND_LABEL:
                if (is_array($id)) {
                    $options = $product->getResource()->getAttribute($attributeCode)->getSource()->getSpecificOptions($id, false);
                    $optionValues = [];

                    foreach ($options as $item) {
                        if (in_array($item['value'], $id)) {
                            $optionValues[] = [
                                'id' => $item['value'],
                                'label' => $item['label']
                            ];
                        }
                    }
                    $value[$attributeCode] = $optionValues;
                } else {
                    $value[$attributeCode] = [
                        'id' => $id,
                        'label' => (string) $product->getAttributeText($attributeCode)
                    ];
                }
                break;
            case Format::OPTION_ID_AND_LABEL_ATTR:
                if (is_array($id)) {
                    $options = $product->getResource()->getAttribute($attributeCode)->getSource()->getSpecificOptions($id, false);

                    if (!isset($value[$attributeCode])) {
                        $value[$attributeCode] = [];
                    }
                    if (!isset($value[$attributeCode . '_oid'])) {
                        $value[$attributeCode . '_oid'] = [];
                    }

                    foreach ($options as $item) {
                        if (in_array($item['value'], $id)) {
                            $value[$attributeCode][] = $item['label'];
                            $value[$attributeCode . '_oid'][] = $item['value'];
                        }
                    }
                } else {
                    $value[$attributeCode] = (string) $product->getAttributeText($attributeCode);
                    $value[$attributeCode . '_oid'] = $id;
                }
                break;
            case Format::OPTION_LABEL:
                if (is_array($id)) {
                    $options = $product->getResource()->getAttribute($attributeCode)->getSource()->getSpecificOptions($id);

                    if (!isset($value[$attributeCode])) {
                        $value[$attributeCode] = [];
                    }

                    foreach ($options as $item) {
                        if (in_array($item['value'], $id)) {
                            $value[$attributeCode][] = $item['label'];
                        }
                    }
                } else {
                    $value[$attributeCode] = (string) $product->getAttributeText($attributeCode);
                }
                break;
            default:
                $value[$attributeCode] = $id;;
        }
    }

    /**
     * Include all filterable attributes flag
     *
     * @param int $storeId
     * @return bool
     */
    protected function includeAllFilterableAttributes(int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PRODUCT_FILTERABLE_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
