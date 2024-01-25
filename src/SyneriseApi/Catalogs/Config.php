<?php

namespace Synerise\Integration\SyneriseApi\Catalogs;

class Config
{
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
     * Get cached catalog ID
     *
     * @param int $storeId
     * @return array|mixed|null
     */
    public function getCatalogId(int $storeId)
    {
        return $this->dataStorage->get($storeId);
    }

    /**
     * Remove cache for selected scope
     *
     * @param int $scopeId
     * @return void
     */
    public function resetByScopeId(int $scopeId)
    {
        $this->dataStorage->resetByScopeId($scopeId);
    }
}
