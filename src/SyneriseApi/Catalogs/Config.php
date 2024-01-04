<?php

namespace Synerise\Integration\SyneriseApi\Catalogs;

use InvalidArgumentException;

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
     * Remove cache reinitialize data
     *
     * @return void
     */
    public function reinitData()
    {
        $this->dataStorage->reinitData();
    }
}
