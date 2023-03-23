<?php

namespace Synerise\Integration\Helper\Api\Factory;

use Synerise\ApiClient\Api\ApiKeyControllerApi;
use Synerise\ApiClient\Configuration;
use Synerise\Integration\Model\ApiConfig;

class ApiKeyFactory extends AbstractApiFactory
{
    /**
     * @var ApiKeyControllerApi[]
     */
    protected $instances;

    /**
     * @param ApiConfig $apiConfig
     * @return ApiKeyControllerApi
     */
    public function create(ApiConfig $apiConfig): ApiKeyControllerApi
    {
        $client = $this->getGuzzleClient($apiConfig);
        $config = clone Configuration::getDefaultConfiguration()
            ->setHost(sprintf('%s/uauth', $apiConfig->getHost()))
            ->setAccessToken($apiConfig->getToken());

        return new ApiKeyControllerApi(
            $client,
            $config
        );
    }

    /**
     * @param ApiConfig $apiConfig
     * @return ApiKeyControllerApi
     */
    public function get(ApiConfig $apiConfig): ApiKeyControllerApi
    {
        $key = md5(serialize($apiConfig));
        if (!isset($this->instances[$key])) {
            $this->instances[$key] = $this->create($apiConfig);
        }

        return $this->instances[$key];
    }
}