<?php
namespace Synerise\Integration\Model\ResourceModel\BusinessProfile;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'synerise_integration_businessprofile_collection';
    protected $_eventObject = 'synerise_integration_businessprofile_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Synerise\Integration\Model\BusinessProfile', 'Synerise\Integration\Model\ResourceModel\BusinessProfile');
    }

}
