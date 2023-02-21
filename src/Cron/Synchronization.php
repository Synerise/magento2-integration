<?php

namespace Synerise\Integration\Cron;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\ValidatorException;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Synchronization\SenderFactory;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Model\ResourceModel\Cron\Queue\CollectionFactory as QueueCollectionFactory;
use Synerise\Integration\Model\ResourceModel\Cron\Status\CollectionFactory as StatusCollectionFactory;
use Synerise\Integration\Model\ResourceModel\Cron\Queue as QueueResourceModel;
use Synerise\Integration\Model\ResourceModel\Cron\Status as StatusResourceModel;

class Synchronization
{
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
     * @var QueueResourceModel
     */
    protected $queueResourceModel;

    /**
     * @var Api
     */
    private $apiHelper;

    /**
     * @var SenderFactory
     */
    protected $senderFactory;

    public function __construct(
        LoggerInterface $logger,
        QueueCollectionFactory $queueCollectionFactory,
        StatusCollectionFactory $statusCollectionFactory,
        QueueResourceModel $queueResourceModel,
        Api $apiHelper,
        SenderFactory $senderFactory
    ) {
        $this->logger = $logger;
        $this->queueCollectionFactory = $queueCollectionFactory;
        $this->statusCollectionFactory = $statusCollectionFactory;
        $this->queueResourceModel = $queueResourceModel;
        $this->apiHelper = $apiHelper;
        $this->senderFactory = $senderFactory;
    }

    /**
     * Cron method synchronizing data by ids.
     * @throws ApiException
     * @throws LocalizedException
     * @throws ValidatorException
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
                $sender = $this->senderFactory->create(
                    $statusItem->getModel(),
                    $statusItem->getStoreId(),
                    $this->apiHelper->getApiConfigByScope($statusItem->getStoreId()),
                    $statusItem->getWebsiteId()
                );
                if (!$sender->isEnabled() || !in_array($statusItem->getStoreId(), $sender->getEnabledStores())) {
                    $statusItem
                        ->setState(StatusResourceModel::STATE_DISABLED)
                        ->save();
                    continue;
                }

                $stopId = $statusItem->getStopId();
                if (!$stopId) {
                    $stopId = $sender->getCurrentLastId();
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

                $sender->sendItems($collection);

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
     * @throws LocalizedException
     * @throws ApiException
     */
    public function processByQueue()
    {
        try {
            $groupedItems = $this->queueResourceModel->getGroupedQueueItems();
            foreach ($groupedItems as $groupedItem) {
                $sender = $this->senderFactory->create(
                    $groupedItem['model'],
                    $groupedItem['store_id'],
                    $this->apiHelper->getApiConfigByScope($groupedItem['store_id'])
                );

                if (!$sender->isEnabled()) {
                    continue;
                }

                $queueCollection = $this->queueCollectionFactory->create()
                    ->addFieldToSelect('entity_id')
                    ->addFieldToFilter('model', $groupedItem['model'])
                    ->addFieldToFilter('store_id', $groupedItem['store_id'])
                    ->setPageSize($sender->getPageSize());

                if (!$queueCollection->getSize()) {
                    continue;
                }

                $entityIds = $queueCollection->getColumnValues('entity_id');

                $items = $sender->getCollectionFilteredByEntityIds(
                    $entityIds
                );

                if (!$items->getSize()) {
                    continue;
                }

                $sender->sendItems($items);
                $sender->deleteItemsFromQueue($entityIds);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to process cron queue', ['exception' => $e]);
            throw $e;
        }
    }
}
