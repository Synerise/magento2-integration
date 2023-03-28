<?php

namespace Synerise\Integration\Helper\Api;

use Exception;
use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\ScopeInterface;
use Synerise\CatalogsApiClient\Api\BagsApi;
use Synerise\CatalogsApiClient\ApiException;
use Synerise\CatalogsApiClient\Model\AddBag;
use Synerise\CatalogsApiClient\Model\Bag;
use Synerise\Integration\Helper\Api\Factory\BagsApiFactory;
use Synerise\Integration\Model\ApiConfig;

class Bags
{
    const XML_PATH_CATALOG_ID = 'synerise/catalog/id';

    /**
     * @var ApiConfig
     */
    protected $apiConfig;

    /**
     * @var Manager
     */
    protected $cacheManager;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var BagsApiFactory
     */
    private $bagsApiFactory;

    public function __construct(
        Manager $cacheManager,
        WriterInterface $configWriter,
        BagsApiFactory $bagsApiFactory,
        ApiConfig $apiConfig
    ) {
        $this->cacheManager = $cacheManager;
        $this->configWriter = $configWriter;
        $this->bagsApiFactory = $bagsApiFactory;
        $this->apiConfig = $apiConfig;
    }

    /**
     * @param $name
     * @return Bag|null
     * @throws ApiException
     */
    public function addBag($name): ?Bag
    {
        $response = $this->getBagsApiInstance()->addBagWithHttpInfo(new AddBag(['name' => $name]));
        return $response ? $response[0]->getData() : null;
    }

    /**
     * @param $storeId
     * @return string
     * @throws Exception|ApiException
     */
    public function addBagAndSaveConfig($storeId): string
    {
        $bag = $this->addBag($this->getCatalogNameByStoreId($storeId));
        if ($bag === null) {
            throw new Exception('Failed to add a bag.');
        }

        return $this->saveConfigAndCleanCache($bag, $storeId);
    }

    /**
     * @param int $storeId
     * @return string
     * @throws ApiException
     * @throws Exception
     */
    public function getOrAddBagAndSaveConfig(int $storeId): string
    {
        $bag = $this->getOrAddBag($this->getCatalogNameByStoreId($storeId));
        if ($bag === null) {
            throw new Exception('Failed to get or add a bag.');
        }

        return $this->saveConfigAndCleanCache($bag, $storeId);
    }

    /**
     * @param $name
     * @return Bag|null
     * @throws ApiException
     */
    public function getOrAddBag($name): ?Bag
    {
        return $this->getBagByName($name) ? : $this->addBag($name);
    }

    /**
     * @param $name
     * @return Bag|null
     * @throws ApiException
     */
    public function getBagByName($name): ?Bag
    {
        $bags = $this->getBagsApiInstance()->getBags($name)->getData();
        foreach ($bags as $bag) {
            if ($bag->getName() == $name) {
                return $bag;
            }
        }

        return null;
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getCatalogNameByStoreId($storeId): string
    {
        return 'store-'.$storeId;
    }

    /**
     * @param Bag $bag
     * @param int $store_id
     * @return string
     * @throws Exception
     */
    public function saveConfigAndCleanCache(Bag $bag, int $store_id): string
    {
        $id = $bag->getId();
        if ($id === null) {
            throw new Exception('Missing bag id.');
        }

        $this->configWriter->save(self::XML_PATH_CATALOG_ID, $id,ScopeInterface::SCOPE_STORE, $store_id);
        $this->cacheManager->clean(['config']);

        return $id;
    }

    /**
     * @return BagsApi
     */
    protected function getBagsApiInstance(): BagsApi
    {
        if (!isset($this->bagsApi)) {
            $this->bagsApi = $this->bagsApiFactory->create($this->apiConfig);
        }

        return $this->bagsApi;
    }
}