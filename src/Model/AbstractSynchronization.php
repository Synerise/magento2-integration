<?php

namespace Synerise\Integration\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Model\Cron\Status;

abstract class AbstractSynchronization
{
    const XML_PATH_CRON_STATUS_PAGE_SIZE = 'synerise/cron_status/page_size';
    const XML_PATH_SYNCHRONIZATION_MODELS = 'synerise/synchronization/models';

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

    /**
     * @var mixed
     */
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

    /**
     * @throws \Synerise\ApiClient\ApiException
     */
    abstract public function sendItems($collection, $storeId, $websiteId = null);

    /**
     * @param Status $status
     * @return mixed
     */
    public function getCollectionFilteredByIdRange($status)
    {
        return $this->createCollectionWithScope($status->getStoreId(), $status->getWebsiteId())
            ->addFieldToFilter(
                static::ENTITY_ID,
                ['gt' => $status->getStartId()]
            )
            ->addFieldToFilter(
                static::ENTITY_ID,
                ['lteq' => $status->getStopId()]
            )
            ->setOrder(static::ENTITY_ID, 'ASC')
            ->setPageSize($this->getPageSize());
    }

    public function getCollectionFilteredByEntityIds($storeId, $entityIds)
    {
        return $this->createCollectionWithScope($storeId)
            ->addFieldToFilter(
                static::ENTITY_ID,
                ['in' => $entityIds]
            )
            ->setOrder(static::ENTITY_ID, 'ASC')
            ->setPageSize($this->getPageSize());
    }

    public function getCurrentLastId($status)
    {
        $collection = $this->createCollectionWithScope($status->getStoreId(), $status->getWebsiteId())
            ->addFieldToSelect(static::ENTITY_ID)
            ->setOrder(static::ENTITY_ID, 'DESC')
            ->setPageSize(1);

        $item = $collection->getFirstItem();

        return $item ? $item->getData(static::ENTITY_ID) : 0;
    }

    abstract protected function createCollectionWithScope($storeId, $websiteId = null);

    protected function getPageSize($storeId = null)
    {
        return $this->scopeConfig->getValue(
            static::XML_PATH_CRON_STATUS_PAGE_SIZE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isEnabled()
    {
        $enabledModels = $this->scopeConfig->getValue(
            static::XML_PATH_SYNCHRONIZATION_MODELS
        );

        return in_array(static::MODEL, $enabledModels);
    }

    public function getEntityIdField()
    {
        return static::ENTITY_ID;
    }

    abstract public function markAllAsUnsent();
}
