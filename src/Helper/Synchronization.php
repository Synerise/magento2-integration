<?php

namespace Synerise\Integration\Helper;

use InvalidArgumentException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\Integration\Model\Config\Source\Synchronization\Model;

class Synchronization
{
    protected const XML_PATH_PAGE_SIZE_ARRAY = [
        'customer' => 'synerise/customer/limit',
        'order' => 'synerise/order/limit',
        'product' => 'synerise/product/limit',
        'subscriber' => 'synerise/subscriber/limit'
    ];

    public const XML_PATH_SYNCHRONIZATION_ENABLED = 'synerise/synchronization/enabled';

    public const XML_PATH_SYNCHRONIZATION_MODELS = 'synerise/synchronization/models';

    public const XML_PATH_SYNCHRONIZATION_STORES = 'synerise/synchronization/stores';

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

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
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
     * Check if Message Queue is enabled for events
     *
     * @param int $scopeId
     * @param string $scope
     * @return bool
     */
    public function isEventQueueEnabled(int $scopeId, string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->scopeConfig->isSetFlag(
            Tracking::XML_PATH_QUEUE_ENABLED,
            $scope,
            $scopeId
        );
    }

    /**
     * Check if synchronization is enabled
     *
     * @return bool
     */
    public function isSynchronizationEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(Synchronization::XML_PATH_SYNCHRONIZATION_ENABLED);
    }

    /**
     * Check if model is enabled for synchronization
     *
     * @param string $model
     * @return bool
     * @throws InvalidArgumentException
     */
    public function isEnabledModel(string $model): bool
    {
        if (!isset(Model::OPTIONS[$model])) {
            throw new InvalidArgumentException($model . ' is not a valid data model');
        }
        return in_array($model, $this->enabledModels);
    }

    /**
     * Check if store is enabled for synchronization
     *
     * @param int $storeId
     * @return bool
     */
    public function isEnabledStore(int $storeId): bool
    {
        return in_array($storeId, $this->enabledStores);
    }

    /**
     * Get an array of name of models enabled for synchronization
     *
     * @return string[]
     */
    public function getEnabledModels(): array
    {
        return $this->enabledModels;
    }

    /**
     * Get an array IDs of stores enabled for synchronization
     *
     * @return int[]
     */
    public function getEnabledStores(): array
    {
        return $this->enabledStores;
    }

    /**
     * Get website ID by store ID
     *
     * @param int $storeId
     * @return int
     * @throws NoSuchEntityException
     */
    public function getWebsiteIdByStoreId(int $storeId): int
    {
        return $this->storeManager->getStore($storeId)->getWebsiteId();
    }

    /**
     * Get page size from config
     *
     * @param string $model
     * @param int|null $storeId
     * @return int
     */
    public function getPageSize(string $model, ?int $storeId = null): int
    {
        if (!isset(self::XML_PATH_PAGE_SIZE_ARRAY[$model])) {
            throw new InvalidArgumentException('Invalid model');
        }

        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_PAGE_SIZE_ARRAY[$model],
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
