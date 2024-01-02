<?php

namespace Synerise\Integration\MessageQueue;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;

class Filter
{
    public const DEFAULT_PAGE_SIZE = 100;
    
    /**
     * Filter collection by store ID
     *
     * @param AbstractDb $collection
     * @param int $storeId
     * @return AbstractDb
     */
    public function addStoreFilter(AbstractDb $collection, int $storeId): AbstractDb
    {
        if (method_exists($collection, 'addStoreFilter')) {
            return $collection->addStoreFilter($storeId);
        } else {
            return $collection->addFieldToFilter('store_id', ['in' => $storeId]);
        }
    }

    /**
     * Filter collection by entity IDs
     *
     * @param AbstractDb $collection
     * @param array $entityIds
     * @param int $storeId
     * @param int|null $pageSize
     * @return AbstractDb
     * @throws LocalizedException
     */
    public function filterByEntityIds(
        AbstractDb $collection,
        array $entityIds,
        int $storeId,
        ?int $pageSize = null
    ): AbstractDb {
        $idFieldName = $this->getIdFieldName($collection);
        $collection
            ->addFieldToFilter($idFieldName, ['in' => $entityIds])
            ->setOrder($idFieldName, 'ASC')
            ->setPageSize($pageSize ?: self::DEFAULT_PAGE_SIZE);

        return $this->addStoreFilter($collection, $storeId);
    }

    /**
     * Filter collection by entity ID
     *
     * @param AbstractDb $collection
     * @param int $entityId
     * @param int $storeId
     * @param int|null $pageSize
     * @return AbstractDb
     * @throws LocalizedException
     */
    public function filterByEntityId(
        AbstractDb $collection,
        int $entityId,
        int $storeId,
        ?int $pageSize = null
    ): AbstractDb {
        $idFieldName = $this->getIdFieldName($collection);
        $collection
            ->addFieldToFilter(
                $idFieldName,
                ['eq' => $entityId]
            )
            ->setOrder($idFieldName, 'ASC')
            ->setPageSize($pageSize ?: self::DEFAULT_PAGE_SIZE);

        return $this->addStoreFilter($collection, $storeId);
    }

    /**
     * Get ID field name from the collection
     *
     * @param AbstractDb $collection
     * @return string
     * @throws LocalizedException
     */
    public function getIdFieldName(AbstractDb $collection): string
    {
        return $collection->getResource()->getIdFieldName();
    }

    /**
     * Get current last id from the collection
     *
     * @param AbstractDb $collection
     * @return int
     * @throws LocalizedException
     */
    public function getLastId(AbstractDb $collection): int
    {
        $idFieldName = $this->getIdFieldName($collection);
        $collection
            ->addFieldToSelect($idFieldName)
            ->setOrder($idFieldName, 'DESC')
            ->setPageSize(1);

        $item = $collection->getFirstItem();
        return $item ? (int) $item->getData($idFieldName) : 0;
    }
}
