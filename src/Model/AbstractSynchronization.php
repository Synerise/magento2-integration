<?php

namespace Synerise\Integration\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Model\Cron\Status;
use Synerise\Integration\ResourceModel\Cron\Queue as QueueResourceModel;
use Synerise\Integration\ResourceModel\Cron\Status as StatusResourceModel;

abstract class AbstractSynchronization
{
    const XML_PATH_CRON_STATUS_PAGE_SIZE = 'synerise/cron_status/page_size';
    const XML_PATH_SYNCHRONIZATION_MODELS = 'synerise/synchronization/models';
    const XML_PATH_SYNCHRONIZATION_STORES = 'synerise/synchronization/stores';

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

    /**
     * @var string[]
     */
    protected $enabledModels;

    /**
     * @var QueueResourceModel
     */
    protected $queueResourceModel;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface      $logger,
        ResourceConnection   $resource,
        QueueResourceModel   $queueResourceModel,
                             $collectionFactory
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->connection = $resource->getConnection();
        $this->queueResourceModel = $queueResourceModel;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @throws \Synerise\ApiClient\ApiException
     */
    abstract public function sendItems($collection, $storeId, $websiteId = null);

    /**
     * @param \Magento\Framework\Data\Collection\AbstractDb $collection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addItemsToQueue($collection)
    {
        $enabledStores = $this->getEnabledStores();

        foreach ($collection as $item) {
            if (in_array($item->getStoreId(), $enabledStores)) {
                $data[] = [
                    'model' => static::MODEL,
                    'store_id' => $item->getStoreId(),
                    'entity_id' => $item->getData(static::ENTITY_ID),
                ];
            }
        }

        if (!empty($data)) {
            $this->queueResourceModel->addItems($data);
        }
    }

    /**
     * @param string $storeId
     * @param array $entityIds
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteItemsFromQueue($storeId, $entityIds)
    {
        $this->queueResourceModel->deleteItems(static::MODEL, $storeId, $entityIds);
    }

    /**
     * @param Status $status
     * @return Collection
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

    public function getPageSize($storeId = null)
    {
        return $this->scopeConfig->getValue(
            static::XML_PATH_CRON_STATUS_PAGE_SIZE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getEnabledModels()
    {
        if (!isset($this->enabledModels)) {
            $enabledModels = $this->scopeConfig->getValue(
                static::XML_PATH_SYNCHRONIZATION_MODELS
            );

            $this->enabledModels = explode(',', $enabledModels);
        }

        return $this->enabledModels;
    }

    public function isEnabled()
    {
        $enabledModels = $this->getEnabledModels();
        return in_array(static::MODEL, $enabledModels);
    }

    public function getEntityIdField()
    {
        return static::ENTITY_ID;
    }

    abstract public function markAllAsUnsent();

    /**
     * @return array
     */
    public function getEnabledStores()
    {
        $enabledStoresString = $this->scopeConfig->getValue(
            self::XML_PATH_SYNCHRONIZATION_STORES
        );

        return $enabledStoresString ? explode(',', $enabledStoresString) : [];
    }
}
