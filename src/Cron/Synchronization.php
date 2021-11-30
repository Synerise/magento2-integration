<?php

namespace Synerise\Integration\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Model\Cron\QueueFactory;
use Synerise\Integration\Model\Cron\StatusFactory;
use Synerise\Integration\Model\Synchronization\Subject\Resolver;

class Synchronization
{
    const CRON_STATUS_STATE_IN_PROGRESS = 0;
    const CRON_STATUS_STATE_COMPLETE= 1;
    const CRON_STATUS_STATE_RETRY_REQUIRED = 2;
    const CRON_STATUS_STATE_ERROR = 3;

    const XML_PATH_CRON_QUEUE_PAGE_SIZE = 'synerise/cron_queue/page_size';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    protected $queueFactory;

    protected $statusFactory;

    protected $storeStatusCollectionCache;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        QueueFactory $queueFactory,
        StatusFactory $statusFactory,
        ResourceConnection $resource,
        Resolver $subjectResolver
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->queueFactory = $queueFactory;
        $this->statusFactory = $statusFactory;
        $this->connection = $resource->getConnection();
        $this->resource = $resource;
        $this->subjectResolver = $subjectResolver;
    }

    public function processByIds()
    {
        try {
            $statusCollection = $this->statusFactory->create()
                ->getCollection()
                ->addFieldToFilter('state', static::CRON_STATUS_STATE_IN_PROGRESS);

            if (!$statusCollection->count()) {
                return;
            }

            foreach ($statusCollection as $statusItem) {
                $subject = $this->subjectResolver->create($statusItem->getModel());
                if ($subject) {
                    if (!$subject->isEnabled()) {
                        continue;
                    }

                    $stopId = $statusItem->getStopId();
                    if (!$stopId) {
                        $stopId = $subject->getCurrentLastId();
                        $statusItem->setStopId($stopId);
                    }

                    $startId = $statusItem->getStartId();
                    if ($startId == $stopId) {
                        $statusItem
                            ->setState(static::CRON_STATUS_STATE_COMPLETE)
                            ->save();
                        continue;
                    }

                    $collection = $subject->getCollectionFilteredByIdRange(
                        $statusItem->getStoreId(),
                        $statusItem->getStartId(),
                        $statusItem->getStopId()
                    );

                    if (!$collection->getSize()) {
                        continue;
                    }

                    $subject->sendItems($collection, $statusItem);

                    $lastItem = $collection->getLastItem();
                    $statusItem->setStartId($lastItem->getData($subject->getEntityIdField()));
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
            $collection = $this->statusFactory->create()
                ->getCollection();

            foreach ($collection as $statusItem) {
                $subject = $this->subjectResolver->create($statusItem->getModel());
                if ($subject) {

                    if (!$subject->isEnabled()) {
                        continue;
                    }

                    $queueCollection = $this->queueFactory->create()
                        ->getCollection()
                        ->addFieldToSelect('entity_id')
                        ->addFieldToFilter('model', $statusItem->getModel())
                        ->addFieldToFilter('store_id', $statusItem->getStoreId())
                        ->setPageSize($this->getPageSize());

                    if (!$queueCollection->getSize()) {
                        continue;
                    }

                    $entityIds = $this->getAllEntityIds($queueCollection);

                    $collection = $subject->getCollectionFilteredByEntityIds(
                        $statusItem->getStoreId(),
                        $entityIds
                    );

                    if (!$collection->getSize()) {
                        continue;
                    }

                    $subject->sendItems($collection, $statusItem);

                    $this->deleteItemsFromQueue(
                        $statusItem->getModel(),
                        $statusItem->getStoreId(),
                        $entityIds
                    );
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to process cron queue', ['exception' => $e]);
        }
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

    public function addItemToQueueByWebsiteIds($model, $websiteIds, $entityId)
    {
        $collection = $this->getStoreStatusCollectionByWebsiteIds($model, $websiteIds);

        if ($collection->count()) {
            $data = [];
            foreach ($collection as $status) {
                $data[] = [
                    'model' => $model,
                    'store_id' => $status->getStoreId(),
                    'entity_id' => $entityId,
                ];
            }

            $this->connection->insertOnDuplicate(
                $this->connection->getTableName('synerise_cron_queue'),
                $data
            );
        }
    }

    public function addItemsToQueueByItemWebsiteIds($model, $items)
    {
        $data = [];
        foreach ($items as $item) {
            $collection = $this->getStoreStatusCollectionByWebsiteIds($model, $item->getWebsiteIds());
            if ($collection->count()) {
                foreach ($collection as $status) {
                    $data[] = [
                        'model' => $model,
                        'store_id' => $status->getStoreId(),
                        'entity_id' => $item->getEntityId(),
                    ];
                }
            }
        }

        $this->connection->insertOnDuplicate(
            $this->connection->getTableName('synerise_cron_queue'),
            $data
        );
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

    public function getAllEntityIds($collection)
    {
        $ids = [];
        foreach ($collection as $item) {
            $ids[] = $item->getEntityId();
        }
        return $ids;
    }

    protected function getStoreStatusCollectionByWebsiteIds($model, $websiteIds)
    {
        sort($websiteIds);
        $key = $model . implode($websiteIds, '|');
        if (!isset($this->storeStatusCollectionCache[$key])) {
            $collection = $this->statusFactory->create()
                ->getCollection()
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
                'stop_id' => null
            ],
            ['model = ?' => $model]
        );

        $subject = $this->subjectResolver->create($model);
        $subject->markAllAsUnsent();
    }

    public function resetStopId($model)
    {
        $this->connection->update(
            $this->connection->getTableName('synerise_cron_status'),
            [
                'stop_id' => null
            ],
            ['model = ?' => $model]
        );
    }

    protected function getPageSize()
    {
        return $this->scopeConfig->getValue(static::XML_PATH_CRON_QUEUE_PAGE_SIZE);
    }
}
