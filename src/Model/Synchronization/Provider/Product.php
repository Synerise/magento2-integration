<?php

namespace Synerise\Integration\Model\Synchronization\Provider;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Synerise\Integration\Model\Config\Source\Products\Attributes;
use Synerise\Integration\Model\Synchronization\ProviderInterface;
use Synerise\Integration\Model\Synchronization\Sender\Product as Sender;

class Product implements ProviderInterface
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        CollectionFactory $collectionFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->scopeConfig = $scopeConfig;
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
            ->addStoreFilter($storeId);
        return $this;
    }

    public function addAttributesToSelect($storeId)
    {
        $this->getCollection()->addAttributeToSelect($this->getAttributesToSelect($storeId));
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

    /**
     * @param $gt
     * @param $le
     * @param $pageSize
     * @return self
     */
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

    public function getAttributesToSelect($storeId = null)
    {
        $attributes = $this->getEnabledAttributes($storeId);
        return array_merge(
            $attributes,
            Attributes::REQUIRED
        );
    }

    public function getEnabledAttributes($storeId = null)
    {
        $attributes = $this->scopeConfig->getValue(
            Attributes::XML_PATH_PRODUCTS_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $attributes ? explode(',', $attributes) : [];
    }

    public function getCurrentLastId($storeId)
    {
        $collection = $this->collectionFactory->create()
            ->addStoreFilter($storeId)
            ->addFieldToSelect(Sender::ENTITY_ID)
            ->setOrder(Sender::ENTITY_ID, 'DESC')
            ->setPageSize(1);

        $item = $collection->getFirstItem();

        return $item ? (int) $item->getData(Sender::ENTITY_ID) : 0;
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