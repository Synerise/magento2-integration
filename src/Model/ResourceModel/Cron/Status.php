<?php
namespace Synerise\Integration\Model\ResourceModel\Cron;

use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Store\Model\StoreManagerInterface;

class Status extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    const STATE_IN_PROGRESS = 0;
    const STATE_COMPLETE = 1;
    const STATE_RETRY_REQUIRED = 2;
    const STATE_ERROR = 3;
    const STATE_DISABLED = 4;

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
        Context $context,
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;

        parent::__construct($context);
    }

    protected function _construct()
    {
        $this->_init('synerise_cron_status', 'id');
    }

    /**
     * @param $models
     * @param array $enabledStoreIds
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function enableByModels($models, $enabledStoreIds = [])
    {
        if (empty($enabledStoreIds)) {
            return;
        }

        $allStores = $this->storeManager->getStores();
        $rows = [];

        foreach($models as $model) {
            foreach($enabledStoreIds as $storeId) {
                if(!isset($allStores[$storeId])) {
                    continue;
                }

                $rows[] = [
                    'model' => $model,
                    'state' => self::STATE_IN_PROGRESS,
                    'store_id' => $storeId,
                    'website_id' => $allStores[$storeId]->getWebsiteId()
                ];
            }
        }

        if(!empty($rows)) {
            $this->getConnection()->insertOnDuplicate(
                $this->getMainTable(),
                $rows
            );
        }
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function disableAll()
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            ['state' => self::STATE_DISABLED]
        );
    }

    /**
     * @param string $model
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resendItems($model)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            [
                'start_id' => null,
                'stop_id' => null,
                'state' => self::STATE_IN_PROGRESS
            ],
            ['model = ?' => $model]
        );
    }

    /**
     * @param string $model
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resetStopId($model)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            [
                'stop_id' => null,
                'state' => self::STATE_IN_PROGRESS
            ],
            ['model = ?' => $model]
        );
    }
}
