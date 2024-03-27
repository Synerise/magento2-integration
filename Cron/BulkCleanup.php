<?php

namespace Synerise\Integration\Cron;

use Magento\AsynchronousOperations\Api\Data\BulkSummaryInterface;
use Magento\AsynchronousOperations\Model\BulkStatus\CalculatedStatusSql;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Bulk\OperationInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Synerise\Integration\Model\Bulk as BulkModel;
use Synerise\Integration\Model\ResourceModel\Bulk\CollectionFactory;

class BulkCleanup
{
    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var CalculatedStatusSql
     */
    private $calculatedStatusSql;

    /**
     * @var CollectionFactory
     */
    private $bulkCollectionFactory;

    /**
     * @param MetadataPool $metadataPool
     * @param ResourceConnection $resourceConnection
     * @param CalculatedStatusSql $calculatedStatusSql
     * @param CollectionFactory $bulkCollectionFactory
     */
    public function __construct(
        MetadataPool $metadataPool,
        ResourceConnection $resourceConnection,
        CalculatedStatusSql $calculatedStatusSql,
        CollectionFactory $bulkCollectionFactory
    ) {
        $this->metadataPool = $metadataPool;
        $this->resourceConnection = $resourceConnection;
        $this->calculatedStatusSql = $calculatedStatusSql;
        $this->bulkCollectionFactory = $bulkCollectionFactory;
    }

    /**
     * Remove all expired bulks and corresponding operations
     *
     * @return void
     */
    public function execute()
    {
        $operationTableName = $this->resourceConnection->getTableName('magento_operation');
        $collection = $this->bulkCollectionFactory->create()
            ->addFieldToFilter('status', [
                'in' => [OperationInterface::STATUS_TYPE_OPEN, BulkModel::STATUS_TYPE_TO_BE_CANCELED]
            ]);

        $select = $collection->getSelect();
        $select->columns(['operation_status' => $this->calculatedStatusSql->get($operationTableName)]);

        foreach ($collection->getItems() as $item) {
            $status = (int) $item->getStatus();
            if ($status == OperationInterface::STATUS_TYPE_OPEN) {
                if ($item->getData('operation_status') != OperationInterface::STATUS_TYPE_OPEN) {
                    $item->setStatus($item->getData('operation_status'))->save();
                }
            } elseif ($status == BulkModel::STATUS_TYPE_TO_BE_CANCELED) {
                $metadata = $this->metadataPool->getMetadata(BulkSummaryInterface::class);
                $connection = $this->resourceConnection->getConnectionByName($metadata->getEntityConnectionName());
                $connection->delete($metadata->getEntityTable(), ['`uuid` = ?' => $item->getUuid()]);
                $item->setStatus(BulkModel::STATUS_TYPE_CANCELED)->save();
            }
        }
    }
}
