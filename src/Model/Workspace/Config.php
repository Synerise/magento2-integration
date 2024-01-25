<?php

namespace Synerise\Integration\Model\Workspace;

use Synerise\Integration\Model\Workspace\Config\DataFactory;

class Config
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
}
