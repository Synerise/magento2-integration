<?php

namespace Synerise\Integration\SyneriseApi;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Synerise\ApiClient\Api\ApiKeyControllerApi;
use Synerise\ApiClient\Api\AuthenticationControllerApi;
use Synerise\ApiClient\Api\DefaultApi;
use Synerise\ApiClient\Api\TrackerControllerApi;
use Synerise\CatalogsApiClient\Api\BagsApi;
use Synerise\CatalogsApiClient\Api\ItemsApi;

class InstanceFactory
{
    public const API_CLASSES = [
        'authentication' => AuthenticationControllerApi::class,
        'default' => DefaultApi::class,
        'catalogs' => BagsApi::class,
        'items' => ItemsApi::class,
        'tracker' => TrackerControllerApi::class,
        'apiKey' => ApiKeyControllerApi::class
    ];

    public const API_CONFIGURATION_CLASSES = [
        'authentication' => \Synerise\ApiClient\Configuration::class,
        'default' => \Synerise\ApiClient\Configuration::class,
        'catalogs' => \Synerise\CatalogsApiClient\Configuration::class,
        'items' => \Synerise\CatalogsApiClient\Configuration::class,
        'tracker' => \Synerise\ApiClient\Configuration::class,
        'apiKey' => \Synerise\ApiClient\Configuration::class
    ];

    public const API_PATH_FORMATS = [
        'authentication' => '%s/v4',
        'default' => '%s/v4',
        'catalogs' => '%s/catalogs',
        'items' => '%s/catalogs',
        'tracker' => '%s/business-profile-service',
        'apiKey' => '%s/uauth'
    ];

    /**
     * @var float
     */
    protected $timeout = 2.5;

    /**
     * Create API instance by type
     *
     * @param string $type
     * @param Config $config
     * @return mixed
     */
    public function createApiInstance(string $type, Config $config)
    {
        $class = self::API_CLASSES[$type];
        $configurationClass = self::API_CONFIGURATION_CLASSES[$type];

        return new $class(
            new Client($this->getGuzzleClientOptions(
                $config->getTimeout() ?: $this->timeout,
                $config->getAuthorizationType(),
                $config->getAuthorizationToken(),
                $config->getHandlerStack(),
                $config->isKeepAliveEnabled(),
            )),
            clone $configurationClass::getDefaultConfiguration()
                ->setUserAgent($config->getUserAgent())
                ->setHost(sprintf(self::API_PATH_FORMATS[$type], $config->getApiHost()))
                ->setAccessToken(
                    $config->getAuthorizationType() == Config::AUTHORIZATION_TYPE_BEARER ?
                        $config->getAuthorizationToken() : null
                )
        );
    }

    /**
     * Get Guzzle client options
     *
     * @param float|null $timeout
     * @param string|null $authorizationType
     * @param string|null $authorizationToken
     * @param HandlerStack|null $handlerStack
     * @param bool|null $keepAlive
     * @return array
     */
    private function getGuzzleClientOptions(
        ?float $timeout = null,
        ?string $authorizationType = null,
        ?string $authorizationToken = null,
        ?HandlerStack $handlerStack = null,
        ?bool $keepAlive = false
    ): array {
        $options = [
            'connect_timeout' => $timeout ?: $this->timeout,
            'timeout' => $timeout ?: $this->timeout,
            'headers' => []
        ];

        if ($authorizationType && $authorizationType == Config::AUTHORIZATION_TYPE_BASIC) {
            $options['headers']['Authorization'] = [ Config::AUTHORIZATION_TYPE_BASIC . " {$authorizationToken}" ];
        }

        if ($handlerStack) {
            $options['handler'] = $handlerStack;
        }

        if ($keepAlive) {
            $options['headers']['Connection'] = [ 'keep-alive' ];
        }

        return $options;
    }
}
