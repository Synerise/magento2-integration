<?php
namespace Synerise\Integration\ResourceModel;

class BusinessProfile extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('synerise_business_profile', 'id');
    }

}
