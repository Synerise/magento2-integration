<?php

namespace Synerise\Integration\Helper\Api;

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
        $key = md5(serialize(func_get_args()));
        if (!isset($this->instances[$key])) {
            $client = $this->getGuzzleClient();
            $config = clone Configuration::getDefaultConfiguration()
                ->setHost(sprintf('%s/v4', $apiConfig->getHost()));

            if ($apiConfig->getToken()) {
                $config->setAccessToken($apiConfig->getToken());
            }

            $this->instances[$key] = new AuthenticationControllerApi(
                $client,
                $config
            );
        }

        return $this->instances[$key];
    }
}