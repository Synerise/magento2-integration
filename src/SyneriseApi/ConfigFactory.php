<?php

namespace Synerise\Integration\SyneriseApi;

use GuzzleHttp\HandlerStack;
use Loguzz\Middleware\LogMiddleware;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Filter\TranslitUrl;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Loguzz\Formatter\RequestCurlSanitizedFormatter;
use Synerise\Integration\Model\Config\Backend\Workspace;

class ConfigFactory
{
    public const XML_PATH_API_HOST = 'synerise/api/host';

    public const XML_PATH_API_KEY = 'synerise/api/key';

    public const XML_PATH_API_LOGGER_ENABLED = 'synerise/api/logger_enabled';

    public const XML_PATH_API_KEEP_ALIVE_ENABLED = 'synerise/api/keep_alive_enabled';

    public const XML_PATH_API_BASIC_AUTH_ENABLED = 'synerise/api/basic_auth_enabled';

    public const XML_PATH_API_SCHEDULED_REQUEST_TIMEOUT = 'synerise/api/scheduled_request_timeout';

    public const XML_PATH_API_LIVE_REQUEST_TIMEOUT = 'synerise/api/live_request_timeout';

    /**

     * @var TranslitUrl
     */
    private $translitUrl;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Authentication
     */
    private $authentication;

    /**
     * @var string
     */
    private $mode;

    /**
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param TranslitUrl $translitUrl
     * @param StoreManagerInterface $storeManager
     * @param Authentication $authentication
     */
    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        TranslitUrl $translitUrl,
        StoreManagerInterface $storeManager,
        Authentication $authentication
    ) {
        $this->translitUrl = $translitUrl;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->authentication = $authentication;

        // phpcs:ignore
        $this->mode = isset($_SERVER['REQUEST_METHOD']) ? Config::MODE_LIVE : Config::MODE_SCHEDULE;
    }

    /**
     * Create config
     *
     * @param int|null $scopeId
     * @param string $scope
     * @param string|null $mode
     * @return Config
     * @throws ApiException
     * @throws ValidatorException
     */
    public function createConfig(
        ?int $scopeId = null,
        string $scope = ScopeInterface::SCOPE_STORE,
        ?string $mode = null
    ): Config {
        $mode = $mode ?: $this->mode;

        if ($this->isBasicAuthAvailable($scopeId, $scope)) {
            $authorizationType = Config::AUTHORIZATION_TYPE_BASIC;
            $authorizationToken = $this->getBasicToken();
        } else {
            $authorizationType = Config::AUTHORIZATION_TYPE_BEARER;
            $authorizationToken = $this->authentication->getJwt(
                $this->getApiKey($scopeId, $scope),
                $this->createMinimalConfig($scopeId, $scope, $mode)
            );
        }

        return new Config(
            $this->getApiHost($scopeId, $scope),
            $this->getUserAgent($scopeId, $scope),
            $this->getTimeout($mode, $scopeId, $scope),
            $authorizationType,
            $authorizationToken,
            $this->getHandlerStack($scopeId, $scope),
            $this->isKeepAliveEnabled($scopeId, $scope)
        );
    }

    /**
     * Create config with api key
     *
     * @param string $apiKey
     * @param int|null $scopeId
     * @param string $scope
     * @param string|null $mode
     * @return Config
     * @throws ApiException
     * @throws ValidatorException
     */
    public function createConfigWithApiKey(
        string $apiKey,
        ?int $scopeId = null,
        string $scope = ScopeInterface::SCOPE_STORE,
        ?string $mode = null
    ): Config {
        $mode = $mode ?: $this->mode;
        return new Config(
            $this->getApiHost($scopeId, $scope),
            $this->getUserAgent($scopeId, $scope),
            $this->getTimeout($mode, $scopeId, $scope),
            Config::AUTHORIZATION_TYPE_BEARER,
            $this->authentication->getJwt(
                $apiKey,
                $this->createMinimalConfig($scopeId, $scope, $mode)
            ),
            $this->getHandlerStack($scopeId, $scope),
            $this->isKeepAliveEnabled($scopeId, $scope)
        );
    }

    /**
     * Create minimal config
     *
     * @param int|null $scopeId
     * @param string $scope
     * @param string|null $mode
     * @return Config
     */
    public function createMinimalConfig(
        int $scopeId = null,
        string $scope = ScopeInterface::SCOPE_STORE,
        ?string $mode = null
    ): Config {
        $mode = $mode ?: $this->mode;

        return new Config(
            $this->getApiHost($scopeId, $scope),
            $this->getUserAgent($scopeId, $scope),
            $this->getTimeout($mode, $scopeId, $scope)
        );
    }

    /**
     * Get api host
     *
     * @param int|null $scopeId
     * @param string $scope
     * @return string
     */
    public function getApiHost(int $scopeId = null, string $scope = ScopeInterface::SCOPE_STORE): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_API_HOST,
            $scope,
            $scopeId
        );
    }

    /**
     * Get api key
     *
     * @param int|null $scopeId
     * @param string $scope
     * @return string
     */
    public function getApiKey(int $scopeId = null, string $scope = ScopeInterface::SCOPE_STORE): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_API_KEY,
            $scope,
            $scopeId
        );
    }

    /**
     * Get user agent
     *
     * @param int|null $scopeId
     * @param string $scope
     * @return string
     */
    public function getUserAgent(int $scopeId = null, string $scope = ScopeInterface::SCOPE_STORE): string
    {
        $userAgent = 'magento2';

        if ($scope == ScopeInterface::SCOPE_STORE) {
            try {
                $baseUrl = $this->storeManager->getStore($scopeId)->getBaseUrl();
                $domain = preg_replace('/^(http(s)?:\/\/)?((www.)?)/', '', $baseUrl);

                if ($domain) {
                    $userAgent .= '-' . $this->translitUrl->filter($domain);
                }
            } catch (NoSuchEntityException $e) {
                $this->logger->debug('Store not found');
            }
        }

        return $userAgent;
    }

    /**
     * Guid & api key based token
     *
     * @param int|null $scopeId
     * @param string $scope
     * @return string
     */
    public function getBasicToken(int $scopeId = null, string $scope = ScopeInterface::SCOPE_STORE): string
    {
        return $this->scopeConfig->getValue(
            Workspace::XML_PATH_API_BASIC_TOKEN,
            $scope,
            $scopeId
        );
    }

    /**
     * Get handler stack
     *
     * @param int|null $scopeId
     * @param string $scope
     * @return HandlerStack|null
     */
    public function getHandlerStack(int $scopeId = null, string $scope = ScopeInterface::SCOPE_STORE): ?HandlerStack
    {
        $handlerStack = null;
        if ($this->isLoggerEnabled($scopeId, $scope)) {
            $LogMiddleware = new LogMiddleware(
                $this->logger,
                ['request_formatter' => new RequestCurlSanitizedFormatter()]
            );

            $handlerStack = HandlerStack::create();
            $handlerStack->push($LogMiddleware, 'logger');
        }

        return $handlerStack;
    }

    /**
     * Check if logger is enabled
     *
     * @param int|null $scopeId
     * @param string $scope
     * @return bool
     */
    public function isLoggerEnabled(int $scopeId = null, string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_API_LOGGER_ENABLED,
            $scope,
            $scopeId
        );
    }

    /**
     * Check if keep-alive is enabled
     *
     * @param int|null $scopeId
     * @param string $scope
     * @return bool
     */
    public function isKeepAliveEnabled(int $scopeId = null, string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_API_KEEP_ALIVE_ENABLED,
            $scope,
            $scopeId
        );
    }

    /**
     * Check if basic auth is available
     *
     * @param int|null $scopeId
     * @param string $scope
     * @return bool
     */
    public function isBasicAuthAvailable(int $scopeId = null, string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return ($this->isBasicAuthEnabled($scopeId, $scope) && $this->getBasicToken($scopeId, $scope));
    }

    /**
     * Check if basic auth is enabled
     *
     * @param int|null $scopeId
     * @param string $scope
     * @return bool
     */
    public function isBasicAuthEnabled(int $scopeId = null, string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_API_BASIC_AUTH_ENABLED,
            $scope,
            $scopeId
        );
    }

    /**
     * Get timeout
     *
     * @param string $mode
     * @param int|null $scopeId
     * @param string $scope
     * @return float|null
     */
    protected function getTimeout(
        string $mode,
        int $scopeId = null,
        string $scope = ScopeInterface::SCOPE_STORE
    ): ?float {
        if ($mode == Config::MODE_LIVE) {
            return $this->getLiveRequestTimeout($scopeId, $scope);
        } elseif ($mode == Config::MODE_SCHEDULE) {
            return $this->getScheduledRequestTimeout($scopeId, $scope);
        }
        return null;
    }

    /**
     * Get live request timeout
     *
     * @param int|null $scopeId
     * @param string $scope
     * @return string
     */
    public function getLiveRequestTimeout(int $scopeId = null, string $scope = ScopeInterface::SCOPE_STORE): string
    {
        return (float) $this->scopeConfig->getValue(
            self::XML_PATH_API_LIVE_REQUEST_TIMEOUT,
            $scope,
            $scopeId
        );
    }

    /**
     * Get scheduled request timeout
     *
     * @param int|null $scopeId
     * @param string $scope
     * @return string
     */
    public function getScheduledRequestTimeout(int $scopeId = null, string $scope = ScopeInterface::SCOPE_STORE): string
    {
        return (float) $this->scopeConfig->getValue(
            self::XML_PATH_API_SCHEDULED_REQUEST_TIMEOUT,
            $scope,
            $scopeId
        );
    }
}
