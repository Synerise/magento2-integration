<?php

namespace Synerise\Integration\Model\MessageQueue;

class Retry extends \Magento\Framework\Model\AbstractModel
{

    protected function _construct()
    {
        $this->_init('Synerise\Integration\Model\ResourceModel\MessageQueue\Retry');
    }
}
