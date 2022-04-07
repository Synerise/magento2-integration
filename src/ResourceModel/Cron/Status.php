<?php
namespace Synerise\Integration\ResourceModel\Cron;

class Status extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    ) {
        parent::__construct($context);
    }

    protected function _construct()
    {
        $this->_init('synerise_cron_status', 'id');
    }
}
