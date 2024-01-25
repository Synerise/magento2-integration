<?php
namespace Synerise\Integration\Model\ResourceModel\Workspace;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * @var string
     */
    protected $_eventPrefix = 'synerise_integration_workspace_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'synerise_integration_workspace_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Synerise\Integration\Model\Workspace::class,
            \Synerise\Integration\Model\ResourceModel\Workspace::class
        );
    }
}
