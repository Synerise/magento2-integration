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

    public function getCatalogId(int $storeId)
    {
        return $this->dataStorage->get($storeId);
    }

    /**
     * Remove cache for selected scope
     *
     * @return void
     */
    public function resetByScopeId(int $scopeId)
    {
        $this->dataStorage->resetByScopeId($scopeId);
    }
}
