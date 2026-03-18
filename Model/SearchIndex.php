<?php

namespace Synerise\Integration\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * @method getEntityId()
 * @method getIndexId()
 * @method getIndexName()
 * @method getItemsCatalogId()
 * @method getStoreId()
 * @method getCreatedAt()
 * @method getUpdatedAt()
 * @method setIndexId(string $indexId)
 * @method setItemsCatalogId(int $itemsCatalogId)
 * @method setStoreId(int $storeId)
 * @method setIndexName(string $indexName)
 */
class SearchIndex extends AbstractModel implements SearchIndexInterface
{

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'synerise_search_index';

    /**
     * Workspace
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Synerise\Integration\Model\ResourceModel\SearchIndex::class);
    }
}