<?php

namespace Synerise\Integration\Plugin;

use Magento\Catalog\Model\ResourceModel\Product\Website\Link;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Indexer\ActionInterface;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\MessageQueue\Publisher\Data\Batch as Publisher;
use Synerise\Integration\Model\Config\Source\Debug\Exclude;
use Synerise\Integration\Model\Synchronization\Config as SynchronizationConfig;
use Synerise\Integration\SyneriseApi\Sender\Data\Product as Sender;

class ProductIndexStock
{
    /**
     * @var int[]
     */
    private $productIds;

    /**
     * @var array
     */
    private $productStatusesBefore;

    /**
     * @var int[]
     */
    private $storeIds;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var Link
     */
    private $productWebsiteLink;

    /**
     * @var Batch
     */
    private $publisher;

    /**
     * @var SynchronizationConfig
     */
    private $synchronizationConfig;

    /**
     * @var Logger
     */
    private $loggerHelper;

    /**
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     * @param StockConfigurationInterface $stockConfiguration
     * @param Link $productWebsiteLink
     * @param Publisher $publisher
     * @param SynchronizationConfig $synchronizationConfig
     * @param Logger $loggerHelper
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        StockConfigurationInterface $stockConfiguration,
        Link $productWebsiteLink,
        Publisher $publisher,
        SynchronizationConfig $synchronizationConfig,
        Logger $loggerHelper
    ) {
        $this->resource = $resource;
        $this->storeManager = $storeManager;
        $this->stockConfiguration = $stockConfiguration;
        $this->productWebsiteLink = $productWebsiteLink;
        $this->publisher = $publisher;
        $this->synchronizationConfig = $synchronizationConfig;
        $this->loggerHelper = $loggerHelper;
    }

    /**
     * Check stock status before reindex
     *
     * @param ActionInterface $subject
     * @param array $productIds
     * @return void
     */
    public function beforeExecuteList(ActionInterface $subject, array $productIds)
    {
        if (!$this->synchronizationConfig->isModelEnabled(Sender::MODEL)) {
            return;
        }

        if (!$this->productStatusesBefore) {
            $parentIds = $this->getRelationsByChild($productIds);
            $this->productIds = $parentIds ? array_unique(array_merge($parentIds, $productIds)) : $productIds;
            $this->productStatusesBefore = $this->getProductStockStatuses($this->productIds);
        }
    }

    /**
     * Publish products to message queue if stocks status changed
     *
     * @param ActionInterface $subject
     * @return void
     */
    public function afterExecuteList(ActionInterface $subject)
    {
        if (!$this->synchronizationConfig->isModelEnabled(Sender::MODEL)) {
            return;
        }

        if (empty($this->productStatusesBefore)) {
            return;
        }

        $productStatusesAfter = $this->getProductStockStatuses($this->productIds);
        $productIds = $this->getProductIdsForSynchronization($this->productStatusesBefore, $productStatusesAfter);
        if ($productIds) {
            $this->publishForEachStore($productIds);
        }
    }

    /**
     * Publish products to synchronization queue for each store
     *
     * @param int[] $productIds
     * @return void
     */
    protected function publishForEachStore(array $productIds)
    {
        $data = [];
        foreach ($productIds as $productId) {
            foreach ($this->productWebsiteLink->getWebsiteIdsByProductId($productId) as $websiteId) {
                try {
                    foreach ($this->getStoreIds($websiteId) as $storeId) {
                        if ($this->synchronizationConfig->isStoreConfigured($storeId)) {
                            $data[$storeId]['productIds'][] = $productId;
                            $data[$storeId]['websiteId'] = $websiteId;
                        }
                    }
                } catch (NoSuchEntityException $e) {
                    if (!$this->loggerHelper->isExcludedFromLogging(Exclude::EXCEPTION_PRODUCT_NOT_FOUND)) {
                        $this->loggerHelper->warning($e->getMessage());
                    }
                } catch (\Exception $e) {
                    $this->loggerHelper->error($e);
                }
            }
        }

        foreach ($data as $storeId => $item) {
            $this->publisher->publish(Sender::MODEL, $item['productIds'], $storeId, $item['websiteId']);
        }
    }

    /**
     * Retrieve product relations by children
     *
     * @param int|array $childIds
     * @return array
     */
    public function getRelationsByChild($childIds)
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()->from(
            ['cpe' => $this->resource->getTableName('catalog_product_entity')],
            'entity_id'
        )->join(
            ['relation' => $this->resource->getTableName('catalog_product_relation')],
            'relation.parent_id = cpe.entity_id'
        )->where('child_id IN(?)', $childIds, \Zend_Db::INT_TYPE);
        return $connection->fetchCol($select);
    }

    /**
     * Get current stock statuses for product ids.
     *
     * @param array $productIds
     * @return array
     */
    private function getProductStockStatuses(array $productIds)
    {
        $select = $this->resource->getConnection()->select()
            ->from(
                ['css' => $this->resource->getTableName('cataloginventory_stock_status')],
                ['product_id', 'stock_status', 'qty']
            )
            ->joinLeft(
                ['cpr' => $this->resource->getTableName('catalog_product_relation')],
                'css.product_id = cpr.child_id',
                []
            )
            ->joinLeft(
                ['cpe' => $this->resource->getTableName('catalog_product_entity')],
                'cpr.parent_id = cpe.entity_id',
                ['parent_id' => 'cpe.entity_id']
            )
            ->where('product_id IN (?)', $productIds, \Zend_Db::INT_TYPE)
            ->where('stock_id = ?', Stock::DEFAULT_STOCK_ID)
            ->where('website_id = ?', $this->stockConfiguration->getDefaultScopeId());

        $statuses = [];
        foreach ($this->resource->getConnection()->fetchAll($select) as $item) {
            $statuses[$item['product_id']] = $item;
        }
        return $statuses;
    }

    /**
     * Return list of product ids that has data changes
     *
     * @param array $productStatusesBefore
     * @param array $productStatusesAfter
     * @return array
     */
    private function getProductIdsForSynchronization(array $productStatusesBefore, array $productStatusesAfter)
    {
        $disabledProductsIds = array_diff(array_keys($productStatusesBefore), array_keys($productStatusesAfter));
        $enabledProductsIds = array_diff(array_keys($productStatusesAfter), array_keys($productStatusesBefore));
        $commonProductsIds = array_intersect(array_keys($productStatusesBefore), array_keys($productStatusesAfter));
        $productIds = array_merge($disabledProductsIds, $enabledProductsIds);

        $stockThresholdQty = $this->stockConfiguration->getStockThresholdQty();

        foreach ($commonProductsIds as $productId) {
            $statusBefore = $productStatusesBefore[$productId];
            $statusAfter = $productStatusesAfter[$productId];

            if ($statusBefore['stock_status'] !== $statusAfter['stock_status']
                || ($stockThresholdQty && $statusAfter['qty'] <= $stockThresholdQty)) {
                $productIds[] = $productId;
                if (isset($statusAfter['parent_id'])) {
                    $productIds[] = $statusAfter['parent_id'];
                }
            }
        }

        return array_unique($productIds);
    }

    /**
     * Get all sore ids where product is presented
     *
     * @param int $websiteId
     * @return array
     * @throws LocalizedException|NoSuchEntityException
     */
    public function getStoreIds(int $websiteId): array
    {
        if (!isset($this->storeIds[$websiteId])) {
            $this->storeIds[$websiteId] = $this->storeManager->getWebsite($websiteId)->getStoreIds();
        }
        return $this->storeIds[$websiteId];
    }
}
