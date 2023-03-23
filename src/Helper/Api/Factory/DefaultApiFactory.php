<?php

namespace Synerise\Integration\Helper\Api\Factory;

use Synerise\ApiClient\Api\DefaultApi;
use Synerise\ApiClient\Configuration;
use Synerise\Integration\Model\ApiConfig;

class DefaultApiFactory extends AbstractApiFactory
{
    /**
     * @var DefaultApi[]
     */
    protected $instances;

    /**
     * @param ApiConfig $apiConfig
     * @return DefaultApi
     */
    public function create(ApiConfig $apiConfig): DefaultApi
    {
        $client = $this->getGuzzleClient($apiConfig);
        $config = Configuration::getDefaultConfiguration()
            ->setHost(sprintf('%s/v4', $apiConfig->getHost()))
            ->setAccessToken($apiConfig->getToken());

        return new DefaultApi(
            $client,
            $config
        );
    }

    /**
     * @param ApiConfig $apiConfig
     * @return DefaultApi
     */
    public function get(ApiConfig $apiConfig): DefaultApi
    {
        $key = md5(serialize($apiConfig));
        if (!isset($this->instances[$key])) {
            $this->instances[$key] = $this->create($apiConfig);
        }

        return $this->instances[$key];
    }
}