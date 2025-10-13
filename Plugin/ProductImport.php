<?php

namespace Synerise\Integration\Plugin;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogImportExport\Model\Import\Product;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\ImportExport\Model\Import;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Model\Synchronization\Config;
use Synerise\Integration\Model\Tracking\ConfigFactory;
use Synerise\Integration\Observer\Data\ProductImportBunchDelete;
use Synerise\Integration\SyneriseApi\Sender\Data\Product as Sender;

class ProductImport
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var Config
     */
    protected $synchronization;

    /**
     * @var ProductRepositoryInterface|null
     */
    protected $productRepository;

    /**
     * @var string|null
     */
    protected $originalBehavior;

    /**
     * @var int[]
     */
    protected $storeIds;

    /**
     * @var Product[]
     */
    protected $productsToDelete = [];

    public function __construct(
        StoreManagerInterface $storeManager,
        Logger $loggerHelper,
        ConfigFactory $configFactory,
        Config $synchronization,
        ?ProductRepositoryInterface $productRepository = null
    ) {
        $this->storeManager = $storeManager;
        $this->loggerHelper = $loggerHelper;
        $this->configFactory = $configFactory;
        $this->synchronization = $synchronization;
        $this->productRepository = $productRepository ?? ObjectManager::getInstance()
            ->get(ProductRepositoryInterface::class);
    }

    /**
     * Check the original behavior before the import starts
     *
     * @param Product $subject
     * @return void
     */
    public function beforeImportData(Product $subject)
    {
        if (!isset($this->originalBehavior)) {
            $this->originalBehavior = $subject->getBehavior();
        }
    }

    /**
     * Load products to delete for delete behavior
     *
     * @param Product $subject
     * @param $result
     * @param array $rowData
     * @param $rowNum
     * @return mixed
     */
    public function afterValidateRow(Product $subject, $result, array $rowData, $rowNum)
    {
        if ($this->synchronization->isModelEnabled(Sender::MODEL) &&
            $this->getOriginalBehavior() == Import::BEHAVIOR_DELETE &&
            Product::SCOPE_DEFAULT == $subject->getRowScope($rowData) &&
            $result
        ) {
            try {
                $this->loadProductsToDelete(strtolower($rowData[Product::COL_SKU]));
            } catch (\Exception $e) {
                $this->loggerHelper->error($e);
            }
        }

        return $result;
    }

    /**
     * Get the initial import behavior saved on start
     *
     * @return string|null
     */
    public function getOriginalBehavior(): ?string
    {
        return $this->originalBehavior;
    }

    /**
     * Get loaded valid products to delete
     *
     * @return array
     */
    public function getProductsToDelete(): array
    {
        return $this->productsToDelete;
    }

    /**
     * Load products to delete
     *
     * @param string $sku
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function loadProductsToDelete(string $sku)
    {
        $baseProduct = $this->retrieveProductBySku($sku);
        if ($baseProduct) {
            $websiteIds = $baseProduct->getWebsiteIds();
            foreach ($websiteIds as $websiteId) {
                foreach ($this->getEnabledStoreIds($websiteId, ProductImportBunchDelete::EVENT) as $storeId) {
                    if (!isset($this->productsToDelete[$storeId])) {
                        $this->productsToDelete[$storeId] = [
                            'website_id' => $websiteId,
                            'products' => []
                        ];
                    }

                    if ($storeId == $baseProduct->getStoreId()) {
                        $this->productsToDelete[$storeId]['products'][] = $baseProduct;
                    } else {
                        $this->productsToDelete[$storeId]['products'][] = $this->retrieveProductBySku($sku, $storeId);
                    }
                }
            }
        }
    }

    /**
     * Get all store ids for website id and event
     *
     * @param int $websiteId
     * @param string $eventName
     * @return array
     * @throws LocalizedException
     */
    protected function getEnabledStoreIds(int $websiteId, string $eventName): array
    {
        if (!isset($this->storeIds[$websiteId])) {
            $this->storeIds[$websiteId] = [];

            foreach ($this->storeManager->getWebsite($websiteId)->getStoreIds() as $storeId) {
                if ($this->configFactory->get($storeId)->isEventTrackingEnabled($eventName)) {
                    $this->storeIds[$websiteId][] = $storeId;
                }
            }
        }
        return $this->storeIds[$websiteId];
    }

    /**
     * Retrieve product by sku.
     *
     * @param string $sku
     * @param int|null $storeId
     * @return \Magento\Catalog\Model\Product|null
     */
    private function retrieveProductBySku(string $sku, ?int $storeId = null): ?\Magento\Catalog\Model\Product
    {
        try {
            $product = $this->productRepository->get($sku, $storeId);
        } catch (NoSuchEntityException $e) {
            return null;
        }
        return $product;
    }
}
