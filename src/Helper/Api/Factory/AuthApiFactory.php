<?php

namespace Synerise\Integration\Helper\Api\Factory;

use Synerise\ApiClient\Api\AuthenticationControllerApi;
use Synerise\ApiClient\Configuration;
use Synerise\Integration\Model\ApiConfig;

class AuthApiFactory extends AbstractApiFactory
{
    /**
     * @var AuthenticationControllerApi[]
     */
    protected $instances;

    /**
     * @param ApiConfig $apiConfig
     * @return AuthenticationControllerApi
     */
    public function create(ApiConfig $apiConfig): AuthenticationControllerApi
    {
        $client = $this->getGuzzleClient($apiConfig);
        $config = clone Configuration::getDefaultConfiguration()
            ->setHost(sprintf('%s/v4', $apiConfig->getHost()));

        if ($apiConfig->getToken()) {
            $config->setAccessToken($apiConfig->getToken());
        }

        return new AuthenticationControllerApi(
            $client,
            $config
        );
    }

    /**
     * @param ApiConfig $apiConfig
     * @return AuthenticationControllerApi
     */
    public function get(ApiConfig $apiConfig): AuthenticationControllerApi
    {
        $key = md5(serialize($apiConfig));
        if (!isset($this->instances[$key])) {
            $this->instances[$key] = $this->create($apiConfig);
        }

        return $this->instances[$key];
    }
}