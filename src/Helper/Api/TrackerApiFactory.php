<?php

namespace Synerise\Integration\Helper\Api;

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
        $key = md5(serialize($apiConfig));
        if (!isset($this->instances[$key])) {
            $client = $this->getGuzzleClient();
            $config = clone \Synerise\ApiClient\Configuration::getDefaultConfiguration()
                ->setHost(sprintf('%s/business-profile-service', $apiConfig->getHost()))
                ->setAccessToken($apiConfig->getToken());

            $this->instances[$key] = new TrackerControllerApi(
                $client,
                $config
            );
        }

        return $this->instances[$key];
    }
}