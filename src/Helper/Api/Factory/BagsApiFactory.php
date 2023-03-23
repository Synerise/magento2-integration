<?php

namespace Synerise\Integration\Helper\Api\Factory;

use Synerise\CatalogsApiClient\Api\BagsApi;
use Synerise\CatalogsApiClient\Configuration;
use Synerise\Integration\Model\ApiConfig;

class BagsApiFactory extends AbstractApiFactory
{
    /**
     * @var BagsApi[]
     */
    protected $instances;

    /**
     * @param ApiConfig $apiConfig
     * @return BagsApi
     */
    public function create(ApiConfig $apiConfig): BagsApi
    {
        $client = $this->getGuzzleClient($apiConfig);
        $config = Configuration::getDefaultConfiguration()
            ->setHost(sprintf('%s/catalogs', $apiConfig->getHost()))
            ->setAccessToken($apiConfig->getToken());

        return new BagsApi(
            $client,
            $config
        );
    }

    /**
     * @param ApiConfig $apiConfig
     * @return BagsApi
     */
    public function get(ApiConfig $apiConfig): BagsApi
    {
        $key = md5(serialize($apiConfig));
        if (!isset($this->instances[$key])) {
            $this->instances[$key] = $this->create($apiConfig);
        }

        return $this->instances[$key];
    }
}