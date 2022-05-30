<?php

namespace Synerise\Integration\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;
use Psr\Log\LoggerInterface;
use Synerise\Integration\ResourceModel\Cron\Queue\CollectionFactory as QueueCollectionFactory;
use Synerise\Integration\ResourceModel\Cron\Status\CollectionFactory as StatusCollectionFactory;
use Synerise\Integration\Model\Synchronization\Customer;
use Synerise\Integration\Model\Synchronization\Order;
use Synerise\Integration\Model\Synchronization\Product;
use Synerise\Integration\Model\Synchronization\Subscriber;
use Synerise\Integration\ResourceModel\Cron\Queue;

class Synchronization
{
    const CRON_STATUS_STATE_IN_PROGRESS = 0;
    const CRON_STATUS_STATE_COMPLETE = 1;
    const CRON_STATUS_STATE_RETRY_REQUIRED = 2;
    const CRON_STATUS_STATE_ERROR = 3;
    const CRON_STATUS_STATE_DISABLED = 4;

    const XML_PATH_CRON_QUEUE_PAGE_SIZE = 'synerise/cron_queue/page_size';
    const XML_PATH_SYNCHRONIZATION_STORES = 'synerise/synchronization/stores';

    /**
     * @var array
     */
    private $enabledStores = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    protected $queueCollectionFactory;

    protected $statusCollectionFactory;

    protected $storeStatusCollectionCache;
    private $queueResourceModel;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        WebsiteCollectionFactory $websiteCollectionFactory,
        QueueCollectionFactory $queueCollectionFactory,
        StatusCollectionFactory $statusCollectionFactory,
        Customer $customer,
        Order $order,
        Product $product,
        Subscriber $subscriber,
        ResourceConnection $resource,
        Queue $queueResourceModel
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->websiteCollectionFactory = $websiteCollectionFactory;
        $this->queueCollectionFactory = $queueCollectionFactory;
        $this->statusCollectionFactory = $statusCollectionFactory;
        $this->connection = $resource->getConnection();
        $this->resource = $resource;
        $this->queueResourceModel = $queueResourceModel;

        $this->executors = [
            'customer' => $customer,
            'subscriber' => $subscriber,
            'product' => $product,
            'order' => $order
        ];
    }

    public function processByIds()
    {
        try {
            $statusCollection = $this->statusCollectionFactory->create()
                ->addFieldToFilter('state', static::CRON_STATUS_STATE_IN_PROGRESS)
                ->setPageSize(3);

            if (!$statusCollection->count()) {
                return;
            }

            foreach ($statusCollection as $statusItem) {
                $enabledStores = $this->getEnabledStores($statusItem->getModel());
                if(!in_array($statusItem->getStoreId(), $enabledStores)) {
                    $statusItem
                        ->setState(static::CRON_STATUS_STATE_DISABLED)
                        ->save();
                    continue;
                }

                $executor = $this->getExecutorByName($statusItem->getModel());

                if ($executor) {
                    if (!$executor->isEnabled()) {
                        /** todo: enable on config change */
                        $statusItem
                            ->setState(static::CRON_STATUS_STATE_DISABLED)
                            ->save();
                        continue;
                    }

                    $stopId = $statusItem->getStopId();
                    if (!$stopId) {
                        $stopId = $executor->getCurrentLastId($statusItem);
                        $statusItem->setStopId($stopId);
                    }

                    $startId = $statusItem->getStartId();
                    if ($startId == $stopId) {
                        $statusItem
                            ->setState(static::CRON_STATUS_STATE_COMPLETE)
                            ->save();
                        continue;
                    }

                    $collection = $executor->getCollectionFilteredByIdRange($statusItem);

                    if (!$collection->getSize()) {
                        $statusItem
                            ->setState(static::CRON_STATUS_STATE_COMPLETE)
                            ->save();
                        continue;
                    }

                    $executor->sendItems($collection, $statusItem->getStoreId(), $statusItem->getWebsiteId());

                    $lastItem = $collection->getLastItem();
                    $statusItem->setStartId($lastItem->getData($executor->getEntityIdField()));
                    if ($startId == $stopId) {
                        $statusItem
                            ->setState(static::CRON_STATUS_STATE_COMPLETE);
                    }

                    $statusItem->save();
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to process cron items', ['exception' => $e]);
        }
    }

    public function processByQueue()
    {
        try {
            $groupedItems = $this->queueResourceModel->getGroupedQueueItems();
            foreach ($groupedItems as $groupedItem) {
                $executor = $this->getExecutorByName($groupedItem['model']);
                if ($executor) {
                    if (!$executor->isEnabled()) {
                        continue;
                    }

                    $queueCollection = $this->queueCollectionFactory->create()
                        ->addFieldToSelect('entity_id')
                        ->addFieldToFilter('model', $groupedItem['model'])
                        ->addFieldToFilter('store_id', $groupedItem['store_id'])
                        ->setPageSize($this->getPageSize($groupedItem['store_id']));

                    if (!$queueCollection->getSize()) {
                        continue;
                    }

                    $entityIds = $queueCollection->getColumnValues('entity_id');

                    $items = $executor->getCollectionFilteredByEntityIds(
                        $groupedItem['store_id'],
                        $entityIds
                    );

                    if (!$items->getSize()) {
                        continue;
                    }

                    $executor->sendItems($items, $groupedItem['store_id']);

                    $this->deleteItemsFromQueue(
                        $groupedItem['model'],
                        $groupedItem['store_id'],
                        $entityIds
                    );
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to process cron queue', ['exception' => $e]);
        }
    }

    /**
     * @param String $name
     * @return \Synerise\Integration\Model\AbstractSynchronization|null
     */
    protected function getExecutorByName(String $name)
    {
        return isset($this->executors[$name]) ? $this->executors[$name] : null;
    }

    public function addItemToQueueByStoreId($model, $storeId, $entityId)
    {
        $data[] = [
            'model' => $model,
            'store_id' => $storeId,
            'entity_id' => $entityId,
        ];

        $this->connection->insertOnDuplicate(
            $this->connection->getTableName('synerise_cron_queue'),
            $data
        );
    }

    public function addItemsToQueue($model, $collection)
    {
        if ($collection->count()) {
            foreach ($collection as $item) {
                $data[] = [
                    'model' => $model,
                    'store_id' => $item->getStoreId(),
                    'entity_id' => $item->getId(),
                ];
            }
        }

        $this->connection->insertOnDuplicate(
            $this->connection->getTableName('synerise_cron_queue'),
            $data
        );
    }

    /**
     * @param \Magento\Catalog\Model\Product[] $products
     */
    public function addProductsToQueue($products)
    {
        $enabledCatalogStores = $this->getEnabledStores('product');
        $data = [];

        foreach ($products as $product) {
            $storeIds = $product->getStoreIds();
            foreach ($storeIds as $storeId) {
                if(in_array($storeId, $enabledCatalogStores)) {
                    $data[] = [
                        'model' => 'product',
                        'store_id' => $storeId,
                        'entity_id' => $product->getId(),
                    ];
                }
            }
        }

        if (!empty($data)) {
            $this->connection->insertOnDuplicate(
                $this->connection->getTableName('synerise_cron_queue'),
                $data
            );
        }
    }

    public function deleteItemsFromQueue($model, $storeId, $entityIds)
    {
        $where = [
            'store_id = ?' => (int) $storeId,
            'model = ?' => $model,
            'entity_id IN (?)' => $entityIds,
        ];

        $this->connection->delete(
            $this->connection->getTableName('synerise_cron_queue'),
            $where
        );
    }

    protected function getStoreStatusCollectionByWebsiteIds($model, $websiteIds)
    {
        sort($websiteIds);
        $key = $model . implode('|', $websiteIds);
        if (!isset($this->storeStatusCollectionCache[$key])) {
            $collection = $this->statusCollectionFactory->create()
                ->addFieldToSelect('store_id')
                ->addFieldToFilter('model', $model)
                ->addFieldToFilter('website_id', ['in' => $websiteIds]);

            $this->storeStatusCollectionCache[$key] = $collection;
        }

        return $this->storeStatusCollectionCache[$key];
    }

    public function resendItems($model)
    {
        $this->connection->update(
            $this->connection->getTableName('synerise_cron_status'),
            [
                'start_id' => null,
                'stop_id' => null,
                'state' => self::CRON_STATUS_STATE_IN_PROGRESS
            ],
            ['model = ?' => $model]
        );

        $executor = $this->getExecutorByName($model);
        $executor->markAllAsUnsent();
    }

    public function resetStopId($model)
    {
        $this->connection->update(
            $this->connection->getTableName('synerise_cron_status'),
            [
                'stop_id' => null,
                'state' => self::CRON_STATUS_STATE_IN_PROGRESS
            ],
            ['model = ?' => $model]
        );
    }

    protected function getPageSize($storeId)
    {
        return $this->scopeConfig->getValue(
            static::XML_PATH_CRON_QUEUE_PAGE_SIZE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getEnabledStores($model)
    {
        if(!empty($this->enabledStores)) {
            $enabledStores = $this->scopeConfig->getValue(
                self::XML_PATH_SYNCHRONIZATION_STORES
            );

            $this->enabledStores = $enabledStores ? explode(',', $enabledStores) : [];
        }

        if ($model == 'customer') {
            return $this->getWebsitesDefaultStores($enabledStores);
        } else {
            return $this->enabledStores;
        }
    }

    protected function getWebsitesDefaultStores($enabledStoreIds)
    {
        $storeIds = [];
        $websites = $this->websiteCollectionFactory->create();
        foreach($websites as $website) {
            $storeId = $website->getDefaultStore()->getId();
            if (in_array($storeId, $enabledStoreIds)) {
                $storeIds[] = $storeId;
            }
        }

        return $storeIds;
    }
}
