<?php

namespace Synerise\Integration\Model;

use Magento\Framework\Model\AbstractModel;

class Bulk extends AbstractModel
{
    public const STATUS_TYPE_TO_BE_CANCELED = 6;
    public const STATUS_TYPE_CANCELED = 7;

    /**
     * Bulk
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Synerise\Integration\Model\ResourceModel\Bulk::class);
    }
}
