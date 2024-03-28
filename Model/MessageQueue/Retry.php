<?php

namespace Synerise\Integration\Model\MessageQueue;

use Magento\Framework\Model\AbstractModel;

class Retry extends AbstractModel
{
    /**
     * Message Retry
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Synerise\Integration\Model\ResourceModel\MessageQueue\Retry::class);
    }
}
