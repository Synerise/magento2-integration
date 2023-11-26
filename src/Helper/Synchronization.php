<?php

namespace Synerise\Integration\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\Integration\Model\Config\Source\Synchronization\Model;

class Synchronization
{
    const XML_PATH_CRON_STATUS_PAGE_SIZE = 'synerise/cron_status/page_size';

    const XML_PATH_SYNCHRONIZATION_MODELS = 'synerise/synchronization/models';

    const XML_PATH_SYNCHRONIZATION_STORES = 'synerise/synchronization/stores';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var string[]
     */
    protected $enabledModels;

    /**
     * @var string[]
     */
    protected $enabledStores;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;

        $enabledModels = $this->scopeConfig->getValue(self::XML_PATH_SYNCHRONIZATION_MODELS);
        $this->enabledModels = !empty($enabledModels) ? explode(',', $enabledModels) : [];

        $enabledStores = $this->scopeConfig->getValue(self::XML_PATH_SYNCHRONIZATION_STORES);
        $this->enabledStores = !empty($enabledStores) ? explode(',', $enabledStores) : [];
    }

    /**
     * @param string $model
     * @return bool
     */
    public function isEnabledModel(string $model) {
        if (!isset(Model::OPTIONS[$model])) {
            throw new \InvalidArgumentException($model . ' is not a valid data model');
        }
        return isset($this->enabledModels[$model]);
    }

    /**
     * @return string[]
     */
    public function getEnabledModels()
    {
        return $this->enabledModels;
    }

    /**
     * @return string[]
     */
    public function getEnabledStores()
    {
        return $this->enabledStores;
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
     * @param int|null $storeId
     * @return int
     */
    public function getPageSize(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_CRON_STATUS_PAGE_SIZE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}