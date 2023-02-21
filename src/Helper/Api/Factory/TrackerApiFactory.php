<?php

namespace Synerise\Integration\Helper\Api\Factory;

use Synerise\ApiClient\Api\TrackerControllerApi;
use Synerise\Integration\Model\ApiConfig;

class TrackerApiFactory extends AbstractApiFactory
{
    /**
     * @var TrackerControllerApi[]
     */
    protected $instances;

    /**
     * @param ApiConfig $apiConfig
     * @return TrackerControllerApi
     */
    public function create(ApiConfig $apiConfig): TrackerControllerApi
    {
        $client = $this->getGuzzleClient();
        $config = clone \Synerise\ApiClient\Configuration::getDefaultConfiguration()
            ->setHost(sprintf('%s/business-profile-service', $apiConfig->getHost()))
            ->setAccessToken($apiConfig->getToken());

        return new TrackerControllerApi(
            $client,
            $config
        );
    }

    /**
     * @param ApiConfig $apiConfig
     * @return TrackerControllerApi
     */
    public function get(ApiConfig $apiConfig): TrackerControllerApi
    {
        $key = md5(serialize($apiConfig));
        if (!isset($this->instances[$key])) {
            $this->instances[$key] = $this->create($apiConfig);
        }

        return $this->instances[$key];
    }
}