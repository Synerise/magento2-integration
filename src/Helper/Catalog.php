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

    /**
     * @var Api
     */
    protected $apiHelper;

    public function __construct(
        Manager $cacheManager,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        Api $apiHelper
    ) {
        $this->cacheManager = $cacheManager;
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->apiHelper = $apiHelper;
    }

    /**
     * @param string $storeId
     * @return mixed
     */
    protected function getConfigCatalogId(string $storeId)
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
     * @param $timeout
     * @return mixed
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \Synerise\ApiClient\ApiException
     * @throws \Synerise\CatalogsApiClient\ApiException
     */
    public function addCatalog($storeId, $timeout = null)
    {
        $addBagRequest = new \Synerise\CatalogsApiClient\Model\AddBag([
            'name' => $this->getCatalogNameByStoreId($storeId)
        ]);

        $response = $this->apiHelper->getBagsApiInstance($storeId, $timeout)
            ->addBagWithHttpInfo($addBagRequest);
        $catalogId = $response[0]->getData()->getId();

        $this->saveConfigCatalogId($catalogId, $storeId);

        return $catalogId;
    }

    /**
     * @param $storeId
     * @param $timeout
     * @return mixed|string
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \Synerise\ApiClient\ApiException
     * @throws \Synerise\CatalogsApiClient\ApiException
     */
    public function getOrAddCatalog($storeId, $timeout = null)
    {
        $catalogId = $this->getConfigCatalogId($storeId);
        if ($catalogId) {
            return $catalogId;
        }

        $catalog = $this->findExistingCatalogByStoreId($storeId);
        if ($catalog) {
            $catalogId = $catalog->getId();
            $this->saveConfigCatalogId($catalog->getId(), $storeId);
        }

        return $catalogId ?: $this->addCatalog($storeId, $timeout);
    }

    /**
     * @param $storeId
     * @return mixed|\Synerise\CatalogsApiClient\Model\Bag|null
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \Synerise\ApiClient\ApiException
     * @throws \Synerise\CatalogsApiClient\ApiException
     */
    protected function findExistingCatalogByStoreId($storeId)
    {
        $catalogName = $this->getCatalogNameByStoreId($storeId);
        $getBagsResponse = $this->apiHelper->getBagsApiInstance($storeId)
            ->getBags($catalogName);

        $existingBags = $getBagsResponse->getData();
        foreach ($existingBags as $bag) {
            if ($bag->getName() == $catalogName) {
                return $bag;
            }
        }

        return null;
    }

    /**
     * @param $storeId
     * @return string
     */
    protected function getCatalogNameByStoreId($storeId)
    {
        return 'store-' . $storeId;
    }
}
