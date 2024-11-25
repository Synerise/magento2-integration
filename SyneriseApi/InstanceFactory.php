<?php

namespace Synerise\Integration\SyneriseApi;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Loguzz\Middleware\LogMiddleware;
use Magento\Framework\Exception\ValidatorException;
use Synerise\ItemsSearchApiClient\Api\ListingApi;
use Synerise\ItemsSearchApiClient\Api\SearchApi;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Configuration;
use Synerise\ApiClient\Api\ApiKeyControllerApi;
use Synerise\ApiClient\Api\DefaultApi;
use Synerise\ApiClient\Api\TrackerControllerApi;
use Synerise\CatalogsApiClient\Api\BagsApi;
use Synerise\CatalogsApiClient\Api\ItemsApi;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Loguzz\Formatter\RequestCurlSanitizedFormatter;
use Synerise\Integration\Model\WorkspaceInterface;
use Synerise\ItemsSearchConfigApiClient\Api\SearchConfigurationApi;

class InstanceFactory
{
    public const API_CLASSES = [
        'default' => DefaultApi::class,
        'catalogs' => BagsApi::class,
        'items' => ItemsApi::class,
        'tracker' => TrackerControllerApi::class,
        'apiKey' => ApiKeyControllerApi::class,
        'search' => SearchApi::class,
        'listing' => ListingApi::class,
        'search-config' => SearchConfigurationApi::class
    ];

    public const API_CONFIGURATION_CLASSES = [
        'default' => \Synerise\ApiClient\Configuration::class,
        'catalogs' => \Synerise\CatalogsApiClient\Configuration::class,
        'items' => \Synerise\CatalogsApiClient\Configuration::class,
        'tracker' => \Synerise\ApiClient\Configuration::class,
        'apiKey' => \Synerise\ApiClient\Configuration::class,
        'search' => \Synerise\ItemsSearchApiClient\Configuration::class,
        'listing' => \Synerise\ItemsSearchApiClient\Configuration::class,
        'search-config' => \Synerise\ItemsSearchConfigApiClient\Configuration::class
    ];

    public const API_PATH_FORMATS = [
        'authentication' => '%s/v4',
        'default' => '%s/v4',
        'catalogs' => '%s/catalogs',
        'items' => '%s/catalogs',
        'tracker' => '%s/business-profile-service',
        'apiKey' => '%s/uauth',
        'search' => '%s',
        'listing' => '%s',
        'search-config' => '%s'
    ];

    /**
     * @var AuthenticatorFactory
     */
    protected $authenticatorFactory;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @param AuthenticatorFactory $authenticatorFactory
     * @param Logger $loggerHelper
     */
    public function __construct(AuthenticatorFactory $authenticatorFactory, Logger $loggerHelper)
    {
        $this->authenticatorFactory = $authenticatorFactory;
        $this->loggerHelper = $loggerHelper;
    }

    /**
     * Create API instance by type
     *
     * @param string $type
     * @param ConfigInterface $apiConfig
     * @param WorkspaceInterface $workspace
     * @return mixed
     * @throws ApiException
     * @throws ValidatorException
     */
    public function createApiInstance(string $type, ConfigInterface $apiConfig, WorkspaceInterface $workspace)
    {
        $class = self::API_CLASSES[$type];
        return new $class(
            new Client($this->getGuzzleClientOptions($apiConfig, $workspace)),
            $this->getInstanceConfig($type, $apiConfig, $workspace)
        );
    }

    /**
     * Get Guzzle client options
     *
     * @param ConfigInterface $apiConfig
     * @param WorkspaceInterface $workspace
     * @return array
     * @throws ValidatorException
     * @throws ApiException
     */
    private function getGuzzleClientOptions(ConfigInterface $apiConfig, WorkspaceInterface $workspace): array
    {
        $options = [
            'connect_timeout' => $apiConfig->getTimeout(),
            'timeout' => $apiConfig->getTimeout(),
            'headers' => []
        ];

        if ($workspace->isBasicAuthEnabled()) {
            $token = base64_encode("{$workspace->getGuid()}:{$workspace->getApiKey()}");
            $authorization = ConfigInterface::AUTHORIZATION_TYPE_BASIC . " {$token}";
        } else {
            $token = $this->authenticatorFactory->create($apiConfig, $workspace)
                ->getAccessToken($workspace->getApiKey());
            $authorization = ConfigInterface::AUTHORIZATION_TYPE_BEARER . " {$token}";
        }

        $options['headers']['Authorization'] = [ $authorization ];

        $handlerStack = $this->getHandlerStack($apiConfig);
        if ($handlerStack) {
            $options['handler'] = $handlerStack;
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
     * @param ConfigInterface $apiConfig
     * @param WorkspaceInterface $workspace
     * @return Configuration|\Synerise\CatalogsApiClient\Configuration
     */
    private function getInstanceConfig(string $type, ConfigInterface $apiConfig, WorkspaceInterface $workspace)
    {
        $configurationClass = self::API_CONFIGURATION_CLASSES[$type];
        return clone $configurationClass::getDefaultConfiguration()
            ->setUserAgent($apiConfig->getUserAgent())
            ->setHost(sprintf(self::API_PATH_FORMATS[$type], $workspace->getApiHost()));
    }

    /**
     * Get handler stack
     *
     * @param ConfigInterface $apiConfig
     * @return HandlerStack|null
     */
    public function getHandlerStack(ConfigInterface $apiConfig): ?HandlerStack
    {
        $handlerStack = null;
        if ($apiConfig->isLoggerEnabled()) {
            $LogMiddleware = new LogMiddleware(
                $this->loggerHelper->getLogger(),
                ['request_formatter' => new RequestCurlSanitizedFormatter()]
            );

            $handlerStack = HandlerStack::create();
            $handlerStack->push($LogMiddleware, 'logger');
        }

        return $handlerStack;
    }
}
