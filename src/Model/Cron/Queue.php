<?php
namespace Synerise\Integration\Model\Cron;

class Queue extends \Magento\Framework\Model\AbstractModel
{
    protected $_eventPrefix = 'synerise_cron_queue';
    protected $_eventObject = 'synerise_cron_queue';

    protected function _construct()
    {
        $this->_init('Synerise\Integration\ResourceModel\Cron\Queue');
    }
}