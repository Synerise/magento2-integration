<?php

namespace Synerise\Integration\Model\Synchronization\Provider;

use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Synerise\Integration\Model\Synchronization\ProviderInterface;
use Synerise\Integration\Model\Synchronization\Sender\Customer as Sender;

class Customer implements ProviderInterface
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Collection
     */
    protected $collection;

    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return Collection
     */
    public function getCollection()
    {
        if (!isset($this->collection)) {
            $this->collection = $this->createCollection();
        }
        return $this->collection;
    }

    public function createCollection()
    {
        $this->collection = $this->collectionFactory->create();
        return $this;
    }

    public function addStoreFilter($storeId)
    {
        $this->getCollection()
            ->addFieldToFilter('store_id', ['in' => $storeId]);
        return $this;
    }

    public function addAttributesToSelect($attributes)
    {
        $this->getCollection()->addAttributeToSelect($attributes);
        return $this;
    }

    /**
     * @param int $entityId
     * @return self
     */
    public function filterByEntityId($entityId)
    {
        $this->getCollection()
            ->addFieldToFilter(
                Sender::ENTITY_ID,
                ['eq' => $entityId]
            )
            ->setOrder(Sender::ENTITY_ID, 'ASC')
            ->setPageSize(1);

        return $this;
    }

    public function filterByEntityIds($entityIds, $pageSize = null)
    {
        $this->getCollection()
            ->addFieldToFilter(
                Sender::ENTITY_ID,
                ['in' => $entityIds]
            )
            ->setOrder(Sender::ENTITY_ID, 'ASC')
            ->setPageSize($pageSize ?: $this->getLimit());
        return $this;
    }

    public function filterByEntityRange($gt, $le, $pageSize = null)
    {
        $this->getCollection()
            ->addFieldToFilter(
                Sender::ENTITY_ID,
                ['gt' => $gt]
            )
            ->addFieldToFilter(
                Sender::ENTITY_ID,
                ['lteq' => $le]
            )
            ->setOrder(Sender::ENTITY_ID, 'ASC')
            ->setPageSize($pageSize ?: $this->getLimit());
        return $this;
    }

    public function getCurrentLastId($storeId)
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('store_id', ['in' => $storeId])
            ->addFieldToSelect(Sender::ENTITY_ID)
            ->setOrder(Sender::ENTITY_ID, 'DESC')
            ->setPageSize(1);

        $item = $collection->getFirstItem();

        return $item ? $item->getData(Sender::ENTITY_ID) : 0;
    }

    protected function getEntityIdField()
    {
        return $this->getCollection()->getIdFieldName();
    }

    /** @todo: add to config */
    public function getLimit()
    {
        return 100;
    }
}