<?php

namespace Synerise\Integration\Model\Synchronization;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Synerise\Integration\Helper\Catalog as CatalogHelper;
use Synerise\Integration\Helper\Queue;
use Synerise\Integration\Model\AbstractSynchronization;

class Product extends AbstractSynchronization
{
    const MODEL = 'product';
    const ENTITY_ID = 'entity_id';

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var catalogHelper
     */
    protected $catalogHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ResourceConnection $resource,
        Queue $queueHelper,
        CollectionFactory $collectionFactory,
        CatalogHelper $catalogHelper
    ) {
        $this->catalogHelper = $catalogHelper;

        parent::__construct(
            $scopeConfig,
            $resource,
            $queueHelper,
            $collectionFactory
        );
    }

    /**
     * @param int $storeId
     * @param int|null $websiteId
     * @return mixed
     */
    protected function createCollectionWithScope($storeId, $websiteId = null)
    {
        return $this->collectionFactory->create()->addStoreFilter($storeId);
    }

    public function sendItems($collection, $storeId, $websiteId = null)
    {
        $attributes = $this->catalogHelper->getProductAttributesToSelect($storeId);
        $collection
            ->addAttributeToSelect($attributes);

        $this->catalogHelper->addItemsBatchWithCatalogCheck(
            $collection,
            $attributes,
            $websiteId,
            $storeId
        );
    }

    public function markAllAsUnsent()
    {
        $this->connection->truncateTable($this->connection->getTableName('synerise_sync_product'));
    }

    /**
     * @param \Magento\Framework\Data\Collection\AbstractDb|array $collection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addItemsToQueue($collection)
    {
        $enabledStores = $this->getEnabledStores();
        foreach ($collection as $item) {
            $storeIds = $item->getStoreIds();
            foreach ($storeIds as $storeId) {
                if (in_array($storeId, $enabledStores)) {
                    $this->queueHelper->publishUpdate(static::MODEL, $storeId, $item->getData(static::ENTITY_ID));
                }
            }
        }

    }
}
