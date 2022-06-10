<?php
namespace Synerise\Integration\ResourceModel\Cron;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Queue extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('synerise_cron_queue', 'id');
    }

    /**
     * @param string $model
     * @param int $storeId
     * @param int $entityId
     * @throws LocalizedException
     */
    public function addItem($model, $storeId, $entityId)
    {
        $this->addItems([
            'model' => $model,
            'store_id' => $storeId,
            'entity_id' => $entityId,
        ]);
    }

    /**
     * @param array $data
     * @throws LocalizedException
     */
    public function addItems($data)
    {
        $this->getConnection()->insertOnDuplicate(
            $this->getMainTable(),
            $data
        );
    }

    /**
     * @param $model
     * @param $storeId
     * @param $entityIds
     * @throws LocalizedException
     */
    public function deleteItems($model, $storeId, $entityIds)
    {
        $this->getConnection()->delete(
            $this->getMainTable(),
            [
                'store_id = ?' => (int) $storeId,
                'model = ?' => $model,
                'entity_id IN (?)' => $entityIds,
            ]
        );
    }

    /**
     * @param int $limit
     * @return array
     * @throws LocalizedException
     */
    public function getGroupedQueueItems($limit = 1000)
    {
        return $this->getConnection()->fetchAll(
            "SELECT store_id, model FROM {$this->getMainTable()} GROUP BY model, store_id LIMIT $limit"
        );
    }
}
