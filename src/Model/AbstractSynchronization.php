<?php

namespace Synerise\Integration\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Model\Cron\Status;


Abstract Class AbstractSynchronization
{
    const XML_PATH_CRON_STATUS_PAGE_SIZE = 'synerise/cron_status/page_size';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    protected $collectionFactory;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        ResourceConnection $resource,
        $collectionFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->connection = $resource->getConnection();
        $this->collectionFactory = $collectionFactory;
    }

    abstract public function sendItems($collection, $status);

    public function getCollectionFilteredByIdRange($storeId, $startId, $stopId)
    {
        return $this->collectionFactory->create()
            ->addFieldToFilter(
                static::ENTITY_ID,
                array('gt' => $startId)
            )
            ->addFieldToFilter(
                static::ENTITY_ID,
                array('lteq' => $stopId)
            )
            ->setOrder(static::ENTITY_ID,'ASC')
            ->setPageSize($this->getPageSize());
    }

    public function getCollectionFilteredByEntityIds($storeId, $entityIds) {
        return $this->collectionFactory->create()
            ->addFieldToFilter(
                static::ENTITY_ID,
                array('in' => $entityIds)
            )
            ->setOrder(static::ENTITY_ID,'ASC')
            ->setPageSize($this->getPageSize());
    }

    public function getCurrentLastId()
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToSelect(static::ENTITY_ID)
            ->setOrder(static::ENTITY_ID, 'DESC')
            ->setPageSize(1);

        $item = $collection->getFirstItem();

        return $item ? $item->getData(static::ENTITY_ID) : 0;
    }

    protected function getPageSize()
    {
        return $this->scopeConfig->getValue(static::XML_PATH_CRON_STATUS_PAGE_SIZE);
    }

    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            static::CONFIG_XML_PATH_CRON_ENABLED
        );
    }

    public function getEntityIdField()
    {
        return static::ENTITY_ID;
    }

    abstract public function markAllAsUnsent();
}
