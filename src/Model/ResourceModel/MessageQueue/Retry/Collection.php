<?php

namespace Synerise\Integration\Model\ResourceModel\MessageQueue\Retry;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Synerise\Integration\Model\MessageQueue\Retry;
use Synerise\Integration\Model\ResourceModel\MessageQueue\Retry as RetryResourceModel;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * @var string
     */
    protected $_eventPrefix = 'synerise_queue_message_retry_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'synerise_queue_message_retry_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            Retry::class,
            RetryResourceModel::class
        );
    }
}
