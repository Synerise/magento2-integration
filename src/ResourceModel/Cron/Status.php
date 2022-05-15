<?php
namespace Synerise\Integration\ResourceModel\Cron;

use \Magento\Config\Model\Config\Source\Website;

class Status extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    const GROUP_TO_MODEL = [
        'products'      => 'product',
        'customers'     => 'customer',
        'order'         => 'order',
        'subscriber'    => 'subscriber'
    ];

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;

        parent::__construct($context);
    }

    protected function _construct()
    {
        $this->_init('synerise_cron_status', 'id');
    }

    /**
     * @param string $model
     * @param int $storeId
     * @param int $websiteId
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function insertOrUpdate($model, $storeId, $websiteId)
    {
        return $this->getConnection()->insertOnDuplicate(
            $this->getMainTable(),
            [
                'model' => $model,
                'store_id' => $storeId,
                'website_id' => $websiteId
            ]
        );
    }

    public function enableByModel($model, $enabledStoreIds = [])
    {

        if (empty($enabledStoreIds)) {
            return;
        }

        $allStores = $this->storeManager->getStores();

        $rows = [];
        foreach($enabledStoreIds as $storeId) {
            if(!isset($allStores[$storeId])) {
                continue;
            }

            $rows[] = [
                'model' => $model,
                'state' => \Synerise\Integration\Model\Cron\Status::STATE_IN_PROGRESS,
                'store_id' => $storeId,
                'website_id' => $allStores[$storeId]->getWebsiteId()
            ];
        }

        if(!empty($rows)) {
            $this->getConnection()->insertOnDuplicate(
                $this->getMainTable(),
                $rows
            );
        }
    }

    public function disableByModel($model, $enabledStoreIds = [])
    {
        $where = ['model = ?' => $model];
        if (!empty($enabledStoreIds)) {
            $where['store_id NOT IN (?)'] = $enabledStoreIds;
        }

        $this->getConnection()->update(
            $this->getMainTable(),
            ['state' => \Synerise\Integration\Model\Cron\Status::STATE_DISABLED],
            $where
        );
    }
}
