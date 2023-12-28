<?php
namespace Synerise\Integration\Model\ResourceModel;

class Workspace extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('synerise_workspace', 'id');
    }
}
