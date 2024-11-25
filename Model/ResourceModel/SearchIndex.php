<?php

namespace Synerise\Integration\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SearchIndex extends AbstractDb
{
    /**
     * Workspace
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('synerise_search_index', 'entity_id');
    }
}