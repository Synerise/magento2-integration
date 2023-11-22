<?php

namespace Synerise\Integration\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class Synchronization
{
    const XML_PATH_CRON_STATUS_PAGE_SIZE = 'synerise/cron_status/page_size';

    const XML_PATH_SYNCHRONIZATION_MODELS = 'synerise/synchronization/models';

    const XML_PATH_SYNCHRONIZATION_STORES = 'synerise/synchronization/stores';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var WebsiteCollectionFactory
     */
    private $websiteCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WebsiteCollectionFactory $websiteCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->websiteCollectionFactory = $websiteCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @return array
     */
    public function getEnabledStores()
    {
        $enabledStoresString = $this->scopeConfig->getValue(
            self::XML_PATH_SYNCHRONIZATION_STORES
        );

        return $enabledStoresString ? explode(',', $enabledStoresString) : [];
    }


    /**
     * @param int $storeId
     * @return int
     * @throws NoSuchEntityException
     */
    public function getWebsiteIdByStoreId(int $storeId)
    {
        return $this->storeManager->getStore($storeId)->getWebsiteId();
    }


    /**
     * Filters enabled stores to return only default ones. Utilized by customer sender.
     *
     * @return array
     */
    public function getDefaultEnabledStores()
    {
        $allEnabledStoreIds = $this->getEnabledStores();
        $storeIds = [];

        $websites = $this->websiteCollectionFactory->create();
        foreach($websites as $website) {
            $storeId = $website->getDefaultStore()->getId();
            if (in_array($storeId, $allEnabledStoreIds)) {
                $storeIds[] = $storeId;
            }
        }

        return $storeIds;
    }

    /**
     * @param int|null $storeId
     * @return int
     */
    public function getPageSize(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_CRON_STATUS_PAGE_SIZE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}