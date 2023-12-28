<?php

namespace Synerise\Integration\Model\ResourceModel\MessageQueue;

class Retry extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('synerise_queue_message_retry', 'id');
    }
}
