<?php

namespace Synerise\Integration\Helper\Api;

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
        $key = md5(serialize($apiConfig));
        if (!isset($this->instances[$key])) {
            $client = $this->getGuzzleClient($apiConfig->isLoggerEnabled());
            $config = Configuration::getDefaultConfiguration()
                ->setHost(sprintf('%s/v4', $apiConfig->getHost()))
                ->setAccessToken($apiConfig->getToken());

            $this->instances[$key] = new DefaultApi(
                $client,
                $config
            );
        }

        return $this->instances[$key];
    }
}