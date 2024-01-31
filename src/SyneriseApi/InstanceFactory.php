<?php

namespace Synerise\Integration\SyneriseApi;

use GuzzleHttp\Client;
use Magento\Framework\Exception\ValidatorException;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Model\WorkspaceInterface;
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
     * @var AuthenticatorFactory
     */
    protected $authenticatorFactory;

    /**
     * @param AuthenticatorFactory $authenticatorFactory
     */
    public function __construct(AuthenticatorFactory $authenticatorFactory)
    {
        $this->authenticatorFactory = $authenticatorFactory;
    }

    /**
     * Create API instance by type
     *
     * @param string $type
     * @param Config $apiConfig
     * @param WorkspaceInterface|null $workspace
     * @return mixed
     * @throws ApiException
     * @throws ValidatorException
     */
    public function createApiInstance(string $type, Config $apiConfig, ?WorkspaceInterface $workspace = null)
    {
        $class = self::API_CLASSES[$type];
        return new $class(
            new Client($this->getGuzzleClientOptions($apiConfig, $workspace)),
            $this->getInstanceConfig($type, $apiConfig)
        );
    }

    /**
     * Get Guzzle client options
     *
     * @param Config $apiConfig
     * @param WorkspaceInterface|null $workspace
     * @return array
     * @throws ValidatorException
     * @throws ApiException
     */
    private function getGuzzleClientOptions(Config $apiConfig, ?WorkspaceInterface $workspace = null): array
    {
        $options = [
            'connect_timeout' => $apiConfig->getTimeout() ?: $this->timeout,
            'timeout' => $apiConfig->getTimeout() ?: $this->timeout,
            'headers' => []
        ];

        if ($workspace) {
            if ($apiConfig->getAuthorizationType() == Config::AUTHORIZATION_TYPE_BEARER) {
                $token = $this->authenticatorFactory->create($apiConfig)->getAccessToken($workspace->getApiKey());
                $authorization = Config::AUTHORIZATION_TYPE_BEARER . " {$token}";

            } else {
                $token = base64_encode("{$workspace->getGuid()}:{$workspace->getApiKey()}");
                $authorization = Config::AUTHORIZATION_TYPE_BASIC . " {$token}";
            }

            $options['headers']['Authorization'] = [ $authorization ];

            if ($apiConfig->getHandlerStack()) {
                $options['handler'] = $apiConfig->getHandlerStack();
            }
        }

        if ($apiConfig->isKeepAliveEnabled()) {
            $options['headers']['Connection'] = [ 'keep-alive' ];
        }

        return $options;
    }

    /**
     * Get Instance Configuration
     *
     * @param string $type
     * @param Config $apiConfig
     * @return \Synerise\ApiClient\Configuration|\Synerise\CatalogsApiClient\Configuration
     */
    private function getInstanceConfig(string $type, Config $apiConfig)
    {
        $configurationClass = self::API_CONFIGURATION_CLASSES[$type];
        return clone $configurationClass::getDefaultConfiguration()
            ->setUserAgent($apiConfig->getUserAgent())
            ->setHost(sprintf(self::API_PATH_FORMATS[$type], $apiConfig->getApiHost()));
    }
}
