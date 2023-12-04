<?php

namespace Synerise\Integration\MessageQueue;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;

class Filter
{
    const DEFAULT_PAGE_SIZE = 100;
    
    /**
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
     * @param AbstractDb $collection
     * @param array $entityIds
     * @param int $storeId
     * @param int|null $pageSize
     * @return AbstractDb
     * @throws LocalizedException
     */
    public function filterByEntityIds(AbstractDb $collection, array $entityIds, int $storeId, ?int $pageSize = null): AbstractDb
    {
        $idFieldName = $this->getIdFieldName($collection);
        $collection
            ->addFieldToFilter($idFieldName, ['in' => $entityIds])
            ->setOrder($idFieldName, 'ASC')
            ->setPageSize($pageSize ?: self::DEFAULT_PAGE_SIZE);

        return $this->addStoreFilter($collection, $storeId);
    }

    /**
     * @param AbstractDb $collection
     * @param int $entityId
     * @param int $storeId
     * @param int|null $pageSize
     * @return AbstractDb
     * @throws LocalizedException
     */
    public function filterByEntityId(AbstractDb $collection, int $entityId, int $storeId, ?int $pageSize = null): AbstractDb
    {
        $idFieldName = $this->getIdFieldName($collection);
        $collection
            ->addFieldToFilter(
                $collection->getIdFieldName(),
                ['eq' => $entityId]
            )
            ->setOrder($collection->getIdFieldName(), 'ASC')
            ->setPageSize($pageSize ?: self::DEFAULT_PAGE_SIZE);

        return $this->addStoreFilter($collection, $storeId);
    }

    /**
     * @param AbstractDb $collection
     * @param int $gt
     * @param int $lteq
     * @param int $storeId
     * @param int|null $pageSize
     * @return AbstractDb
     * @throws LocalizedException
     */
    public function filterByEntityIdRange(AbstractDb $collection, int $gt, int $lteq, int $storeId, ?int $pageSize = null): AbstractDb
    {
        $idFieldName = $this->getIdFieldName($collection);
        $collection
            ->addFieldToFilter($idFieldName, ['gt' => $gt])
            ->addFieldToFilter($idFieldName, ['lteq' => $lteq])
            ->setOrder($idFieldName, 'ASC')
            ->setPageSize($pageSize ?: self::DEFAULT_PAGE_SIZE);

        return $this->addStoreFilter($collection, $storeId);
    }

    /**
     * @param AbstractDb $collection
     * @return string
     * @throws LocalizedException
     */
    public function getIdFieldName(AbstractDb $collection): string
    {
        return $collection->getResource()->getIdFieldName();
    }

    /**
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