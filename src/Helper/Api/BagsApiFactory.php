<?php

namespace Synerise\Integration\Helper\Api;

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
        $key = md5(serialize($apiConfig));
        if (!isset($this->instances[$key])) {
            $client = $this->getGuzzleClient($apiConfig->isLoggerEnabled());
            $config = Configuration::getDefaultConfiguration()
                ->setHost(sprintf('%s/catalogs', $apiConfig->getHost()))
                ->setAccessToken($apiConfig->getToken());

            $this->instances[$key] = new BagsApi(
                $client,
                $config
            );
        }

        return $this->instances[$key];
    }
}