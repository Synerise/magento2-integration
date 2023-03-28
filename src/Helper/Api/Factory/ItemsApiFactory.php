<?php

namespace Synerise\Integration\Helper\Api\Factory;

use Synerise\CatalogsApiClient\Api\ItemsApi;
use Synerise\CatalogsApiClient\Configuration;
use Synerise\Integration\Model\ApiConfig;

class ItemsApiFactory extends AbstractApiFactory
{
    /**
     * @var ItemsApi[]
     */
    protected $instances;

    /**
     * @param ApiConfig $apiConfig
     * @return ItemsApi
     */
    public function create(ApiConfig $apiConfig): ItemsApi
    {
        $client = $this->getGuzzleClient($apiConfig);
        $config = Configuration::getDefaultConfiguration()
            ->setHost(sprintf('%s/catalogs', $apiConfig->getHost()))
            ->setAccessToken($apiConfig->getToken());

        return new ItemsApi(
            $client,
            $config
        );
    }

    /**
     * @param ApiConfig $apiConfig
     * @return ItemsApi
     */
    public function get(ApiConfig $apiConfig): ItemsApi
    {
        $key = md5(serialize($apiConfig));
        if (!isset($this->instances[$key])) {
            $this->instances[$key] = $this->create($apiConfig);
        }

        return $this->instances[$key];
    }
}