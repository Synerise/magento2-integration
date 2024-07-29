<?php

namespace Synerise\Integration\Model\Synchronization;

class Config
{
    public const XML_PATH_SYNCHRONIZATION_MODELS = 'synerise/synchronization/models';

    public const XML_PATH_SYNCHRONIZATION_STORES = 'synerise/synchronization/stores';

    /**
     * @var Config\Data
     */
    protected $dataStorage;

    /**
     * @param Config\Data $dataStorage
     */
    public function __construct(Config\Data $dataStorage)
    {
        $this->dataStorage = $dataStorage;
    }

    /**
     * Check if synchronization is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->dataStorage->get('enabled', false);
    }

    /**
     * Check if store is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isStoreEnabled(?int $storeId): bool
    {
        return in_array($storeId, $this->getEnabledStores());
    }

    /**
     * Check if given store or any store is configured
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isStoreConfigured(?int $storeId = null): bool
    {
        if ($storeId) {
            return in_array($storeId, $this->getConfiguredStores());
        } else {
            return !empty($this->getConfiguredStores());
        }
    }

    /**
     * Check if model is enabled
     *
     * @param string $model
     * @return bool
     */
    public function isModelEnabled(string $model): bool
    {
        return $this->isEnabled() && in_array($model, $this->getEnabledModels());
    }

    /**
     * Get an array of ids of configured stores
     *
     * @return array
     */
    public function getConfiguredStores(): array
    {
        return $this->dataStorage->get('stores_configured', []);
    }

    /**
     * Get an array of enabled store ids
     *
     * @return array
     */
    public function getEnabledStores(): array
    {
        return $this->dataStorage->get('stores', []);
    }

    /**
     * Get an array of enabled models
     *
     * @return array
     */
    public function getEnabledModels(): array
    {
        return $this->dataStorage->get('models', []);
    }

    /**
     * Get limit for model
     *
     * @param string $model
     * @return int
     */
    public function getLimit(string $model): int
    {
        return $this->dataStorage->get('limit/' . $model, 50);
    }
}
