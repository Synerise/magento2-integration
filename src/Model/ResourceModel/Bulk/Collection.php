<?php
namespace Synerise\Integration\Model\ResourceModel\Bulk;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * @var string
     */
    protected $_eventPrefix = 'synerise_integration_bulk_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'synerise_integration_bulk_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Synerise\Integration\Model\Bulk::class,
            \Synerise\Integration\Model\ResourceModel\Bulk::class
        );
    }
}
