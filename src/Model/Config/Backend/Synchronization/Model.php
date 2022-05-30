<?php

namespace Synerise\Integration\Model\Config\Backend\Synchronization;

class Model extends \Magento\Framework\App\Config\Value
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
        \Magento\Store\Model\ResourceModel\Website\CollectionFactory $websiteCollectionFactory,
        \Synerise\Integration\ResourceModel\Cron\Status $statusResourceModel,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->statusResourceModel = $statusResourceModel;
        $this->websiteCollectionFactory = $websiteCollectionFactory;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function afterSave()
    {
        $enabledModels = explode(',', $this->getValue());
        $enabledStoreIds = $this->getFieldsetDataValue('stores');

        $this->statusResourceModel->disableAll();

        foreach (array_keys($enabledModels, 'customer', true) as $key) {
            unset($enabledModels[$key]);

            $defaultStoreIds = $this->getWebsitesDefaultStores($enabledStoreIds);
            if ($defaultStoreIds) {
                $this->statusResourceModel->enableByModels(['customer'], $defaultStoreIds);
            }
        }

        $this->statusResourceModel->enableByModels($enabledModels, $enabledStoreIds);

        return parent::afterSave();
    }

    protected function getModelByGroupId($groupId)
    {
        return \Synerise\Integration\ResourceModel\Cron\Status::GROUP_TO_MODEL[$groupId] ?? null;
    }

    protected function getWebsitesDefaultStores($enabledStoreIds)
    {
        $storeIds = [];
        $websites = $this->websiteCollectionFactory->create();
        foreach($websites as $website) {
            $storeId = $website->getDefaultStore()->getId();
            if (in_array($storeId, $enabledStoreIds)) {
                $storeIds[] = $storeId;
            }
        }

        return $storeIds;
    }
}