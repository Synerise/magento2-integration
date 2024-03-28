<?php

namespace Synerise\Integration\Model\Workspace;

use Synerise\Integration\Model\Workspace\Config\DataFactory;
use Synerise\Integration\Model\WorkspaceInterface;

class Config implements WorkspaceInterface
{
    /**
     * @var Config\Data
     */
    protected $dataStorage;

    /**
     * @var int
     */
    protected $storeId;

    /**
     * @param DataFactory $dataFactory
     * @param int $storeId
     */
    public function __construct(DataFactory $dataFactory, int $storeId)
    {
        $this->dataStorage = $dataFactory->create($storeId);
        $this->storeId = $storeId;
    }

    /**
     * Check if API key is set
     *
     * @return bool
     */
    public function isApiKeySet()
    {
        $apiKey = $this->getApiKey();
        return $apiKey !== null && trim($apiKey) !== '';
    }

    /**
     * Get API key
     *
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        return $this->dataStorage->get('apiKey');
    }

    /**
     * Get GUID
     *
     * @return string|null
     */
    public function getGuid(): ?string
    {
        return $this->dataStorage->get('guid');
    }

    /**
     * Get API host
     *
     * @return string
     */
    public function getApiHost(): string
    {
        return $this->dataStorage->get('apiHost');
    }

    /**
     * Get tracker host
     *
     * @return string
     */
    public function getTrackerHost(): string
    {
        return $this->dataStorage->get('trackerHost');
    }

    /**
     * Check if basic auth is enabled
     *
     * @return bool
     */
    public function isBasicAuthEnabled(): bool
    {
        return $this->dataStorage->get('isBasicAuthEnabled');
    }
}
