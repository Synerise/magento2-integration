<?php

namespace Synerise\Integration\Model\Synchronization;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Config;
use Synerise\Integration\Helper\Catalog as CatalogHelper;
use Synerise\Integration\Model\AbstractSynchronization;

class Product extends AbstractSynchronization
{
    const MODEL = 'product';
    const ENTITY_ID = 'entity_id';
    const CONFIG_XML_PATH_CRON_ENABLED = 'synerise/product/cron_enabled';

    /**
     * @var catalogHelper
     */
    protected $catalogHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        ResourceConnection $resource,
        CollectionFactory $collectionFactory,
        CatalogHelper $catalogHelper
    ) {
        $this->catalogHelper = $catalogHelper;

        parent::__construct(
            $scopeConfig,
            $logger,
            $resource,
            $collectionFactory
        );
    }

    public function getCollectionFilteredByIdRange($storeId, $startId, $stopId)
    {
        $collection = parent::getCollectionFilteredByIdRange($storeId, $startId, $stopId);
        $collection
            ->setStoreId($storeId);

        return $collection;
    }

    public function getCollectionFilteredByEntityIds($storeId, $entityIds)
    {
        $collection = parent::getCollectionFilteredByEntityIds($storeId, $entityIds);
        $collection
            ->setStoreId($storeId);

        return $collection;
    }

    public function sendItems($collection, $status)
    {
        $attributes = $this->catalogHelper->getProductAttributesToSelect();
        $collection
            ->addAttributeToSelect($attributes);

        $this->catalogHelper->addItemsBatchWithCatalogCheck(
            $collection,
            $attributes,
            $status->getWebsiteId(),
            $status->getStoreId()
        );
    }

    public function markAllAsUnsent()
    {
        $this->connection->truncateTable($this->connection->getTableName('synerise_sync_product'));
    }
}
