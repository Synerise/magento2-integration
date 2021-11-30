<?php
namespace Synerise\Integration\ResourceModel\Cron;

class Queue extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('synerise_cron_queue', 'id');
    }
}
