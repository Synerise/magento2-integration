<?php

namespace Synerise\Integration\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Cron\Synchronization\SenderFactory;
use Synerise\Integration\Model\ResourceModel\Cron\Queue\CollectionFactory as QueueCollectionFactory;
use Synerise\Integration\Model\ResourceModel\Cron\Status\CollectionFactory as StatusCollectionFactory;
use Synerise\Integration\Model\ResourceModel\Cron\Queue as QueueResourceModel;
use Synerise\Integration\Model\ResourceModel\Cron\Status as StatusResourceModel;

class Synchronization
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var QueueCollectionFactory
     */
    protected $queueCollectionFactory;

    /**
     * @var StatusCollectionFactory
     */
    protected $statusCollectionFactory;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * @var QueueResourceModel
     */
    protected $queueResourceModel;

    /**
     * @var SenderFactory
     */
    protected $senderFactory;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ResourceConnection $resource,
        LoggerInterface $logger,
        QueueCollectionFactory $queueCollectionFactory,
        StatusCollectionFactory $statusCollectionFactory,
        QueueResourceModel $queueResourceModel,
        SenderFactory $senderFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->connection = $resource->getConnection();
        $this->logger = $logger;
        $this->queueCollectionFactory = $queueCollectionFactory;
        $this->statusCollectionFactory = $statusCollectionFactory;
        $this->queueResourceModel = $queueResourceModel;
        $this->senderFactory = $senderFactory;
    }

    /**
     * Cron method synchronizing data by ids.
     * @throws \Synerise\ApiClient\ApiException
     */
    public function processByIds()
    {
        try {
            $statusCollection = $this->statusCollectionFactory->create()
                ->addFieldToFilter('state', StatusResourceModel::STATE_IN_PROGRESS)
                ->setPageSize(3);

            if (!$statusCollection->count()) {
                return;
            }

            foreach ($statusCollection as $statusItem) {
                $sender = $this->senderFactory->create($statusItem->getModel());
                if (!$sender->isEnabled() || !in_array($statusItem->getStoreId(), $sender->getEnabledStores())) {
                    $statusItem
                        ->setState(StatusResourceModel::STATE_DISABLED)
                        ->save();
                    continue;
                }

                $stopId = $statusItem->getStopId();
                if (!$stopId) {
                    $stopId = $sender->getCurrentLastId($statusItem);
                    $statusItem->setStopId($stopId);
                }

                $startId = $statusItem->getStartId();
                if ($startId == $stopId) {
                    $statusItem
                        ->setState(StatusResourceModel::STATE_COMPLETE)
                        ->save();
                    continue;
                }

                $collection = $sender->getCollectionFilteredByIdRange($statusItem);

                if (!$collection->getSize()) {
                    $statusItem
                        ->setState(StatusResourceModel::STATE_COMPLETE)
                        ->save();
                    continue;
                }

                $sender->sendItems($collection, $statusItem->getStoreId(), $statusItem->getWebsiteId());

                $lastItem = $collection->getLastItem();
                $statusItem->setStartId($lastItem->getData($sender->getEntityIdField()));
                if ($startId == $stopId) {
                    $statusItem
                        ->setState(StatusResourceModel::STATE_COMPLETE);
                }

                $statusItem->save();

            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to process cron items', ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Cron method synchronizing data by queue.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Synerise\ApiClient\ApiException
     */
    public function processByQueue()
    {
        try {
            $groupedItems = $this->queueResourceModel->getGroupedQueueItems();
            foreach ($groupedItems as $groupedItem) {
                $sender = $this->senderFactory->create($groupedItem['model']);
                if (!$sender->isEnabled()) {
                    continue;
                }

                $queueCollection = $this->queueCollectionFactory->create()
                    ->addFieldToSelect('entity_id')
                    ->addFieldToFilter('model', $groupedItem['model'])
                    ->addFieldToFilter('store_id', $groupedItem['store_id'])
                    ->setPageSize($sender->getPageSize($groupedItem['store_id']));

                if (!$queueCollection->getSize()) {
                    continue;
                }

                $entityIds = $queueCollection->getColumnValues('entity_id');

                $items = $sender->getCollectionFilteredByEntityIds(
                    $groupedItem['store_id'],
                    $entityIds
                );

                if (!$items->getSize()) {
                    continue;
                }

                $sender->sendItems($items, $groupedItem['store_id']);
                $sender->deleteItemsFromQueue($groupedItem['store_id'], $entityIds);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to process cron queue', ['exception' => $e]);
            throw $e;
        }
    }
}
