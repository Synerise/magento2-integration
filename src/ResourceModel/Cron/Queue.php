<?php
namespace Synerise\Integration\ResourceModel\Cron;

class Queue extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    ) {
        parent::__construct($context);
    }

    protected function _construct()
    {
        $this->_init('synerise_cron_queue', 'id');
    }

    /**
     * @param int $limit
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getGroupedQueueItems($limit = 1000)
    {
        return $this->getConnection()->fetchAll(
            "SELECT store_id, model FROM {$this->getMainTable()} GROUP BY model, store_id LIMIT $limit"
        );
    }
}
