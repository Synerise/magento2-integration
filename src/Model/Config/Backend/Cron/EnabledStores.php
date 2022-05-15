<?php

namespace Synerise\Integration\Model\Config\Backend\Cron;

class EnabledStores extends \Magento\Framework\App\Config\Value
{
    /**
     * @var \Synerise\Integration\ResourceModel\Cron\Status
     */
    private $statusResourceModel;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Synerise\Integration\ResourceModel\Cron\Status $statusResourceModel,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->statusResourceModel = $statusResourceModel;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function afterSave()
    {
        $model = $this->getGroupId();

        $enabled = $this->getValue();
        if ($enabled) {
            $enabledStoreIds = $this->getFieldsetDataValue('stores');
            $this->statusResourceModel->disableByModel($model, $enabledStoreIds);
            $this->statusResourceModel->enableByModel($model, $enabledStoreIds);
        } else {
            $this->statusResourceModel->disableByModel($model);
        }

        return parent::afterSave();
    }

    protected function getModelByGroupId($groupId)
    {
        return \Synerise\Integration\ResourceModel\Cron\Status::GROUP_TO_MODEL[$groupId] ?? null;
    }
}