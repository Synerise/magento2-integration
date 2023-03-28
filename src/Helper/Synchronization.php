<?php

namespace Synerise\Integration\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Synerise\Integration\Model\ResourceModel\Cron\Queue as QueueResourceModel;
use Synerise\Integration\Model\ResourceModel\Cron\Status as StatusResourceModel;

class Synchronization
{
    const XML_PATH_CRON_STATUS_PAGE_SIZE = 'synerise/cron_status/page_size';

    const XML_PATH_SYNCHRONIZATION_MODELS = 'synerise/synchronization/models';

    const XML_PATH_SYNCHRONIZATION_STORES = 'synerise/synchronization/stores';

    /**
     * @var string[]
     */
    protected $enabledModels = [];

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var QueueResourceModel
     */
    protected $queueResourceModel;

    /**
     * @var StatusResourceModel
     */
    protected $statusResourceModel;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        QueueResourceModel $queueResourceModel,
        StatusResourceModel $statusResourceModel
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->queueResourceModel = $queueResourceModel;
        $this->statusResourceModel = $statusResourceModel;
    }

    /**
     * @param \Magento\Framework\Data\Collection\AbstractDb $collection
     * @throws LocalizedException
     */
    public function addItemsToQueue($collection, string $model, string $entityId)
    {
        $enabledStores = $this->getEnabledStores();

        foreach ($collection as $item) {
            if (in_array($item->getStoreId(), $enabledStores)) {
                $data[] = [
                    'model' => $model,
                    'store_id' => $item->getStoreId(),
                    'entity_id' => $item->getData($entityId),
                ];
            }
        }

        if (!empty($data)) {
            $this->queueResourceModel->addItems($data);
        }
    }

    /**
     * @param string $model
     * @param int $entityId
     * @param int $storeId
     * @throws LocalizedException
     */
    public function addItemToQueue(string $model, int $entityId, int $storeId)
    {
        if (in_array($storeId, $this->getEnabledStores())) {
            $this->queueResourceModel->addItems([
                'model' => $model,
                'store_id' => $storeId,
                'entity_id' => $entityId,
            ]);
        }
    }

    /**
     * @param $collection
     * @param string $model
     * @param string $entityId
     * @return void
     * @throws LocalizedException
     */
    public function addItemsToQueuePerStore($collection, string $model, string $entityId)
    {
        $data = [];
        $enabledStores = $this->getEnabledStores();
        foreach ($collection as $item) {
            $storeIds = $item->getStoreIds();
            foreach ($storeIds as $storeId) {
                if (in_array($storeId, $enabledStores)) {
                    $data[] = [
                        'model' => $model,
                        'store_id' => $storeId,
                        'entity_id' => $item->getData($entityId),
                    ];
                }
            }
        }

        if (!empty($data)) {
            $this->queueResourceModel->addItems($data);
        }
    }

    /**
     * @param string $model
     * @param int $storeId
     * @param array $entityIds
     * @throws LocalizedException
     */
    public function deleteItemsFromQueue(string $model, int $storeId, array $entityIds)
    {
        $this->queueResourceModel->deleteItems($model, $storeId, $entityIds);
    }

    /**
     * @param string $model
     * @return void
     * @throws LocalizedException
     */
    public function resetState(string $model)
    {
        $this->statusResourceModel->resetState($model);
    }

    /**
     * Get an array of models enabled for synchronization
     *
     * @return string[]
     */
    public function getEnabledModels(): array
    {
        if (empty($this->enabledModels)) {
            $enabledModels = $this->scopeConfig->getValue(
                static::XML_PATH_SYNCHRONIZATION_MODELS
            );

            $this->enabledModels = explode(',', $enabledModels);
        }

        return $this->enabledModels;
    }

    /**
     * @return array
     */
    public function getEnabledStores(): array
    {
        $enabledStoresString = $this->scopeConfig->getValue(
            self::XML_PATH_SYNCHRONIZATION_STORES
        );

        return $enabledStoresString ? explode(',', $enabledStoresString) : [];
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getPageSize($storeId)
    {
        return $this->scopeConfig->getValue(
            static::XML_PATH_CRON_STATUS_PAGE_SIZE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}