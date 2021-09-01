<?php
namespace Synerise\Integration\Model\Cron;

class Status extends \Magento\Framework\Model\AbstractModel
{
    protected $_eventPrefix = 'synerise_integration_status';
    protected $_eventObject = 'synerise_integration_status';

    protected function _construct()
    {
        $this->_init('Synerise\Integration\ResourceModel\Cron\Status');
    }
}