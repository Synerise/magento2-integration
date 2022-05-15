<?php
namespace Synerise\Integration\Model\Cron;

class Status extends \Magento\Framework\Model\AbstractModel
{
    const STATE_IN_PROGRESS = 0;
    const STATE_COMPLETE = 1;
    const STATE_RETRY_REQUIRED = 2;
    const STATE_ERROR = 3;
    const STATE_DISABLED = 4;
    
    protected $_eventPrefix = 'synerise_integration_status';
    protected $_eventObject = 'synerise_integration_status';

    protected function _construct()
    {
        $this->_init('Synerise\Integration\ResourceModel\Cron\Status');
    }
}
