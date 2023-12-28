<?php

namespace Synerise\Integration\Helper;

use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\ScopeInterface;

class Catalog
{
    const XML_PATH_CATALOG_ID = 'synerise/catalog/id';

    /**
     * @var Manager
     */
    protected $cacheManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    public function __construct(
        Manager $cacheManager,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
    ) {
        $this->cacheManager = $cacheManager;
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
    }

    /**
     * @param string $storeId
     * @return mixed
     */
    public function getConfigCatalogId(string $storeId)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_CATALOG_ID,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $catalogId
     * @param $store_id
     * @return void
     */
    public function saveConfigCatalogId($catalogId, $store_id)
    {
        $this->configWriter->save(
            self::XML_PATH_CATALOG_ID,
            $catalogId,
            ScopeInterface::SCOPE_STORES,
            $store_id
        );
        $this->cacheManager->clean(['config']);
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getCatalogNameByStoreId($storeId)
    {
        return 'store-' . $storeId;
    }
}
