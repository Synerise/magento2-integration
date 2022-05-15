<?php

namespace Synerise\Integration\Model\Config\Backend\Cron;

class EnabledWebsites extends \Magento\Framework\App\Config\Value
{
    /**
     * @var \Synerise\Integration\ResourceModel\Cron\Status
     */
    private $statusResourceModel;

    /**
     * @var \Magento\Store\Model\ResourceModel\Website\CollectionFactory
     */
    private $websiteCollectionFactory;

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
        $this->websiteCollectionFactory = $websiteCollectionFactory;
        $this->statusResourceModel = $statusResourceModel;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function afterSave()
    {
        $model = $this->getGroupId();
        $websites = $this->getWebsites($this->getFieldsetDataValue('websites'));

        $enabled = $this->getValue();
        if ($enabled) {
            $storeIds = [];
            foreach ($websites as $website) {
                $storeIds[] = $website->getDefaultStore()->getId();
            }
            $this->statusResourceModel->disableByModel($model, $storeIds);
            $this->statusResourceModel->enableByModel($model, $storeIds);
        } else {
            $this->statusResourceModel->disableByModel($model);
        }

        return parent::afterSave();
    }

    /**
     * @param $websiteIds
     * @return \Magento\Store\Model\ResourceModel\Website\Collection
     */
    protected function getWebsites($websiteIds)
    {
        return $this->websiteCollectionFactory->create()
            ->addFieldToFilter('website_id', ['in' => $websiteIds]);
    }
}