<?php

namespace Synerise\Integration\Model\Synchronization;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
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

    public function getSyneriseUpdatedAtAttribute()
    {
        return $this->catalogHelper->getSyneriseUpdatedAtAttribute();
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
        $attribute = $this->getSyneriseUpdatedAtAttribute();
        if ($attribute->getId()) {
            $this->connection->update(
                'catalog_product_entity_datetime',
                ['value' => null],
                ['attribute_id', $attribute->getId()]
            );
        }
    }
}
