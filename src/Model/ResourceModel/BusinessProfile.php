<?php
namespace Synerise\Integration\Model\ResourceModel;

class BusinessProfile extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('synerise_business_profile', 'id');
    }

}
