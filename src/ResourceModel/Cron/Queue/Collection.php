<?php
namespace Synerise\Integration\ResourceModel\Cron\Queue;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'synerise_cron_queues_collection';
    protected $_eventObject = 'synerise_cron_queues_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Synerise\Integration\Model\Cron\Queue::class,
            \Synerise\Integration\ResourceModel\Cron\Queue::class
        );
    }
}
