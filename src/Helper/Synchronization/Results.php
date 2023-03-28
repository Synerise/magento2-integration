<?php

namespace Synerise\Integration\Helper\Synchronization;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Synerise\Integration\Helper\Synchronization\Sender\Customer;
use Synerise\Integration\Helper\Synchronization\Sender\Product;

class Results
{
    const TABLES = [
        'customer' => 'synerise_sync_customer',
        'order' => 'synerise_sync_order',
        'product' => 'synerise_sync_product',
        'subscriber' => 'synerise_sync_subscriber'
    ];

    const INCLUDE_STORE = [
        Customer::MODEL,
        Product::MODEL
    ];

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var DateTime
     */
    private $dateTime;

    public function __construct(
        DateTime $dateTime,
        ResourceConnection $resource
    ) {
        $this->connection = $resource->getConnection();
        $this->dateTime = $dateTime;
    }

    /**
     * @param string $model
     * @param int $id
     * @param int|null $storeId
     * @return int
     */
    public function deleteItem(string $model, int $id, ?int $storeId = null): int
    {
        if($storeId != null) {
            $where = [
                $model.'_id = ?' => $id,
                'store_id = ?' => $storeId
            ];
        } else {
            $where = [ $model.'_id = ?' => $id ];
        }

        return $this->connection->delete(
            $this->connection->getTableName(self::TABLES[$model]),
            $where
        );
    }

    /**
     * @param string $model
     * @param int $id
     * @param int|null $storeId
     * @return bool
     */
    public function isSent(string $model, int $id, ?int $storeId = null): bool
    {
        if (in_array($model, self::INCLUDE_STORE) && $storeId == null) {
            throw New \InvalidArgumentException('Store ID required for model ' . $model);
        }

        $select = $this->connection->select()
            ->from($this->connection->getTableName(self::TABLES[$model]), 'COUNT(*)')
            ->where($model.'_id = ?', $id);

        if ($storeId != null) {
            $select->where('store_id = ?', $storeId);
        }

        return (bool) $this->connection->fetchOne($select);
    }

    /**
     * @param string $model
     * @param int[] $ids
     * @param int|null $storeId
     * @return int
     */
    public function markAsSent(string $model, array $ids, ?int $storeId = null): int
    {
        if (in_array($model, self::INCLUDE_STORE) && $storeId == null) {
            throw New \InvalidArgumentException('Store ID required for model ' . $model);
        }

        $timestamp = $this->dateTime->gmtDate();
        $data = [];
        foreach ($ids as $id) {
            if ($storeId != null) {
                $data[] = [
                    'synerise_updated_at' => $timestamp,
                    $model.'_id' => $id,
                    'store_id' => $storeId
                ];
            } else {
                $data[] = [
                    'synerise_updated_at' => $timestamp,
                    $model.'_id' => $id
                ];
            }
        }

        return $this->connection->insertOnDuplicate(
            $this->connection->getTableName(self::TABLES[$model]),
            $data
        );
    }

    /**
     * @param string $model
     * @return void
     */
    public function truncateTable(string $model)
    {
        $this->connection->truncateTable(
            $this->connection->getTableName(self::TABLES[$model])
        );
    }
}