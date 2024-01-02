<?php

namespace Synerise\Integration\Helper\Product;

use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\ScopeInterface;

class Catalog
{
    public const XML_PATH_CATALOG_ID = 'synerise/catalog/id';

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

    /**
     * @param Manager $cacheManager
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     */
    public function __construct(
        Manager $cacheManager,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter
    ) {
        $this->cacheManager = $cacheManager;
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
    }

    /**
     * Get catalog ID from config
     *
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
     * Save Catalog ID as config value
     *
     * @param int $catalogId
     * @param int $store_id
     * @return void
     */
    public function saveConfigCatalogId(int $catalogId, int $store_id)
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
     * Get catalog name by store ID
     *
     * @param int $storeId
     * @return string
     */
    public function getCatalogNameByStoreId(int $storeId): string
    {
        return 'store-' . $storeId;
    }
}
