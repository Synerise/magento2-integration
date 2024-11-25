<?php

namespace Synerise\Integration\Model\ResourceModel\SearchIndex;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * @var string
     */
    protected $_eventPrefix = 'synerise_integration_search_index_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'synerise_integration_search_index_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Synerise\Integration\Model\SearchIndex::class,
            \Synerise\Integration\Model\ResourceModel\SearchIndex::class
        );
    }
}