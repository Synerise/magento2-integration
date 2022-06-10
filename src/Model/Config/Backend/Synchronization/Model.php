<?php

namespace Synerise\Integration\Model\Config\Backend\Synchronization;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory;
use Synerise\Integration\ResourceModel\Cron\Status;

class Model extends Value
{
    /**
     * @var Status
     */
    private $statusResourceModel;

    /**
     * @var CollectionFactory
     */
    private $websiteCollectionFactory;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        CollectionFactory $websiteCollectionFactory,
        Status $statusResourceModel,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->statusResourceModel = $statusResourceModel;
        $this->websiteCollectionFactory = $websiteCollectionFactory;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return Model
     * @throws \Magento\Framework\Exception\LocalizedException
     */
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

    /**
     * @param string $groupId
     * @return string|null
     */
    protected function getModelByGroupId($groupId)
    {
        return Status::GROUP_TO_MODEL[$groupId] ?? null;
    }

    /**
     * @param array $enabledStoreIds
     * @return array
     */
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