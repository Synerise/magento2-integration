<?php

namespace Synerise\Integration\Model\ResourceModel\MessageQueue;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Retry extends AbstractDb
{
    /**
     * Message Retry
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('synerise_queue_message_retry', 'id');
    }
}
