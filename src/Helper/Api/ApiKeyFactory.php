<?php

namespace Synerise\Integration\Helper\Api;

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
        $key = md5(serialize($apiConfig));
        if (!isset($this->instances[$key])) {
            $client = $this->getGuzzleClient();
            $config = clone Configuration::getDefaultConfiguration()
                ->setHost(sprintf('%s/uauth', $apiConfig->getHost()))
                ->setAccessToken($apiConfig->getToken());

            $this->instances[$key] = new ApiKeyControllerApi(
                $client,
                $config
            );
        }

        return $this->instances[$key];
    }
}