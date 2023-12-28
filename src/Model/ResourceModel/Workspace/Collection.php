<?php
namespace Synerise\Integration\Model\ResourceModel\Workspace;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'synerise_integration_workspace_collection';
    protected $_eventObject = 'synerise_integration_workspace_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Synerise\Integration\Model\Workspace', 'Synerise\Integration\Model\ResourceModel\Workspace');
    }
}
