<?php
namespace Synerise\Integration\Model\ResourceModel\Cron\Status;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'synerise_integration_status_collection';
    protected $_eventObject = 'synerise_integration_status_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Synerise\Integration\Model\Cron\Status',
            'Synerise\Integration\Model\ResourceModel\Cron\Status'
        );
    }
}
