<?php

namespace Synerise\Integration\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Bulk extends AbstractDb
{
    /**
     * Bullk
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('synerise_bulk', 'id');
    }
}
