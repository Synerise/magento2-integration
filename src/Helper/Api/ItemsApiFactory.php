<?php

namespace Synerise\Integration\Helper\Api;

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
        $key = md5(serialize($apiConfig));
        if (!isset($this->instances[$key])) {
            $client = $this->getGuzzleClient($apiConfig->isLoggerEnabled());
            $config = Configuration::getDefaultConfiguration()
                ->setHost(sprintf('%s/catalogs', $apiConfig->getHost()))
                ->setAccessToken($apiConfig->getToken());

            $this->instances[$key] = new ItemsApi(
                $client,
                $config
            );
        }

        return $this->instances[$key];
    }
}