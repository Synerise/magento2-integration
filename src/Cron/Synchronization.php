<?php

namespace Synerise\Integration\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Model\AbstractSynchronization;
use Synerise\Integration\Model\ResourceModel\Cron\Queue\CollectionFactory as QueueCollectionFactory;
use Synerise\Integration\Model\ResourceModel\Cron\Status\CollectionFactory as StatusCollectionFactory;
use Synerise\Integration\Model\Synchronization\Customer;
use Synerise\Integration\Model\Synchronization\Order;
use Synerise\Integration\Model\Synchronization\Product;
use Synerise\Integration\Model\Synchronization\Subscriber;
use Synerise\Integration\Model\ResourceModel\Cron\Queue as QueueResourceModel;
use Synerise\Integration\Model\ResourceModel\Cron\Status as StatusResourceModel;

class Synchronization
{
    const XML_PATH_CRON_QUEUE_PAGE_SIZE = 'synerise/cron_queue/page_size';
    const XML_PATH_SYNCHRONIZATION_STORES = 'synerise/synchronization/stores';

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
     * @var array
     */
    protected $executors;

    /**
     * @var array
     */
    protected $enabledStores = [];

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ResourceConnection $resource,
        LoggerInterface $logger,
        QueueCollectionFactory $queueCollectionFactory,
        StatusCollectionFactory $statusCollectionFactory,
        QueueResourceModel $queueResourceModel,
        Customer $customer,
        Order $order,
        Product $product,
        Subscriber $subscriber
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->connection = $resource->getConnection();
        $this->logger = $logger;
        $this->queueCollectionFactory = $queueCollectionFactory;
        $this->statusCollectionFactory = $statusCollectionFactory;
        $this->queueResourceModel = $queueResourceModel;

        $this->executors = [
            'customer' => $customer,
            'subscriber' => $subscriber,
            'product' => $product,
            'order' => $order
        ];
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
                $executor = $this->getExecutorByName($statusItem->getModel());
                if (!$executor || !$executor->isEnabled() || !in_array($statusItem->getStoreId(), $executor->getEnabledStores())) {
                    /** todo: enable on config change */
                    $statusItem
                        ->setState(StatusResourceModel::STATE_DISABLED)
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
                        ->setState(StatusResourceModel::STATE_COMPLETE)
                        ->save();
                    continue;
                }

                $collection = $executor->getCollectionFilteredByIdRange($statusItem);

                if (!$collection->getSize()) {
                    $statusItem
                        ->setState(StatusResourceModel::STATE_COMPLETE)
                        ->save();
                    continue;
                }

                $executor->sendItems($collection, $statusItem->getStoreId(), $statusItem->getWebsiteId());

                $lastItem = $collection->getLastItem();
                $statusItem->setStartId($lastItem->getData($executor->getEntityIdField()));
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
                $executor = $this->getExecutorByName($groupedItem['model']);
                if (!$executor || !$executor->isEnabled()) {
                    continue;
                }

                $queueCollection = $this->queueCollectionFactory->create()
                    ->addFieldToSelect('entity_id')
                    ->addFieldToFilter('model', $groupedItem['model'])
                    ->addFieldToFilter('store_id', $groupedItem['store_id'])
                    ->setPageSize($executor->getPageSize($groupedItem['store_id']));

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
                $executor->deleteItemsFromQueue($groupedItem['store_id'], $entityIds);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to process cron queue', ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * @param String $name
     * @return AbstractSynchronization|null
     */
    protected function getExecutorByName(String $name)
    {
        return $this->executors[$name] ?? null;
    }
}
