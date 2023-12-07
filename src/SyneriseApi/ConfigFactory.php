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
use Synerise\ApiClient\Model\BusinessProfileAuthenticationRequest;
use Synerise\Integration\Loguzz\Formatter\RequestCurlSanitizedFormatter;
use Synerise\Integration\Model\Config\Backend\Workspace;

class ConfigFactory
{
    const MODE_LIVE = 'live';

    const MODE_SCHEDULE = 'schedule';

    const XML_PATH_API_HOST = 'synerise/api/host';

    const XML_PATH_API_KEY = 'synerise/api/key';

    const XML_PATH_API_LOGGER_ENABLED = 'synerise/api/logger_enabled';

    const XML_PATH_API_KEEP_ALIVE_ENABLED = 'synerise/api/keep_alive_enabled';

    const XML_PATH_API_BASIC_AUTH_ENABLED = 'synerise/api/basic_auth_enabled';

    const XML_PATH_API_SCHEDULED_REQUEST_TIMEOUT = 'synerise/api/scheduled_request_timeout';

    const XML_PATH_API_LIVE_REQUEST_TIMEOUT = 'synerise/api/live_request_timeout';

    protected $config = [];

    protected $apiToken = [
//        'ac683fa7-ded9-4488-84f8-11d30e6bd5b0' => 'eyJhbGciOiJSUzUxMiJ9.eyJzdWIiOiI5OWJjZmNkNzU0YTk4Y2U4OWNiODZmNzNhY2MwNDY0NSIsImF1ZCI6IkFQSSIsInJsbSI6ImJ1c2luZXNzX3Byb2ZpbGUiLCJjdGQiOjE3MDE4NTM5NjU1NzcsImlzcyI6IlN5bmVyaXNlIiwiYnBpIjo2OTgsInNlc3Npb25JZCI6IjM0NjE2NGFlLTY1OTAtNDc1Yi04NjYyLWEyYWI4NmE0YzI5ZCIsImV4cCI6MTcwMTg1NzU2NSwiYXBrIjoiYWM2ODNmYTctZGVkOS00NDg4LTg0ZjgtMTFkMzBlNmJkNWIwIn0.GQp4HSxkZnBhJxmPshw1EoP6Oma91Kz4bQNKf4YH1ko548rydEGvVWun2UDf-tHfaXUfuxeErMqNG_Qa-cQ2iC1mPEWzvnn-SIJ3q1udywEkZxIEjd1P896mJX3qMs97VL41c14_UDRZdCzNl96X_cRAHdRomj6LW1jyCC6X3ljHups8u7qN54hX1uYaqPENql60ToRtW6_Lpwd11j9AQhUOVf9UpBFQKrAP0R6Q3ib458stTMFR9meqNhCStyqEQRZvpMvko0Oyc6JOHMbsamZaRKT3qaXxpZP82z7pg_RNRbO7e0T-3_E1wwEH8iZFI_G91HI7KAPhcrBYpU1rzH58-nQKFXvi8iyVgpN3bREHIM4mN0N68MzeqhEby0fYea0r3hDCitN_gxx8N0jWKpWZwgnua9CW_Hva2zpDlSH04wU5rpXnApOVAwv8MGHccgT6zXCT9KXch2zmupxyAqc0XCIiR79IULzKZxlgntTrig0Adi8mGyq7U2XJyFco6cXjllSa2h8T7g3ZjdMaiP1EhVg2-XyV_J9rydc8K_4gUg_ghFAdnmSST9_IBWzZxWQ_czjURQj3VrppvKyvWnz3mUTzD4sIFA7XB6mV7wCkrFl2hXA32zqn5KsG53htSy8OugY_iVAWzFYcT3568IWyerhLsMz7FLw8t0xjgyI'
    ];

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
     * @var InstanceFactory
     */
    private $instanceFactory;

    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        TranslitUrl $translitUrl,
        StoreManagerInterface $storeManager,
        \Synerise\Integration\SyneriseApi\InstanceFactory $instanceFactory
    ) {
        $this->translitUrl = $translitUrl;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->instanceFactory = $instanceFactory;
    }

    /**
     * @param string $mode
     * @param int|null $scopeId
     * @param string $scope
     * @return Config
     * @throws ApiException
     * @throws ValidatorException
     */
    public function createConfig(string $mode, int $scopeId = null, string $scope = ScopeInterface::SCOPE_STORE): Config
    {
        $timeout = $this->getTimeout($mode, $scopeId, $scope);

        if ($this->isBasicAuthAvailable($scopeId, $scope)) {
            $authorizationType = Config::AUTHORIZATION_TYPE_BASIC;
            $authorizationToken = $this->getBasicToken();
        } else {
            $apiKey = $this->getApiKey($scopeId, $scope);
            $authorizationType = Config::AUTHORIZATION_TYPE_BEARER;
            $authorizationToken = $this->getJwt($apiKey, $scopeId, $timeout, $scope);
        }

        return new Config(
            $this->getApiHost($scopeId, $scope),
            $this->getUserAgent($scopeId, $scope),
            $timeout,
            $this->getScopeKey($mode, $scopeId, $scope),
            $authorizationType,
            $authorizationToken,
            $this->getHandlerStack($scopeId, $scope),
            $this->isKeepAliveEnabled($scopeId, $scope)
        );
    }

    /**
     * @throws ApiException
     * @throws ValidatorException
     */
    public function getConfig(string $mode, int $scopeId = null, string $scope = ScopeInterface::SCOPE_STORE): Config
    {
        $scopeKey = $this->getScopeKey($mode, $scopeId, $scope);
        if (!isset($this->config[$scopeKey][$mode])) {
            $this->config[$scopeKey][$mode] = $this->createConfig($mode, $scopeId, $scope);
        }
        return $this->config[$scopeKey][$mode];
    }


    public function getScopeKey(string $mode, int $scopeId, string $scope = ScopeInterface::SCOPE_STORE)
    {
        return $mode.$scope.$scopeId;
    }

    /**
     * @return void
     */
    public function clearConfig(string $mode, int $scopeId = null, string $scope = ScopeInterface::SCOPE_STORE)
    {
        $this->config[$this->getScopeKey($mode, $scopeId, $scope)] = [];
    }

    /**
     * Api host
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
     * Api key
     *
     * @param string $scope
     * @param int $scopeId
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
     * User Agent
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
                $domain = preg_replace('/^(http(s)?:\/\/)?((www.)?)/','', $baseUrl);

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
     * @param int $scopeId
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
     * Checks if Api Key is set for a given scope
     *
     * @param string $scope
     * @param int $scopeId
     * @return bool
     */
    public function isApiKeySet($scope, $scopeId)
    {
        return (boolean) $this->getApiKey($scopeId, $scope);
    }
    
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

    public function isLoggerEnabled(int $scopeId = null, string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_API_LOGGER_ENABLED,
            $scope,
            $scopeId
        );
    }

    public function isKeepAliveEnabled(int $scopeId = null, string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_API_KEEP_ALIVE_ENABLED,
            $scope,
            $scopeId
        );
    }

    public function isBasicAuthAvailable(int $scopeId = null, string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return ($this->isBaiscAuthEnabled($scopeId, $scope) && $this->getBasicToken($scopeId, $scope));
    }

    public function isBaiscAuthEnabled(int $scopeId = null, string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_API_BASIC_AUTH_ENABLED,
            $scope,
            $scopeId
        );
    }

    protected function getTimeout(string $mode, int $scopeId = null, string $scope = ScopeInterface::SCOPE_STORE): ?float
    {
        if($mode == self::MODE_LIVE) {
            return $this->getLiveRequestTimeout($scopeId, $scope);
        } elseif ($mode == self::MODE_SCHEDULE) {
            return $this->getScheduledRequestTimeout($scopeId, $scope);
        }
        return null;
    }

    public function getLiveRequestTimeout(int $scopeId = null, string $scope = ScopeInterface::SCOPE_STORE): string
    {
        return (float) $this->scopeConfig->getValue(
            self::XML_PATH_API_LIVE_REQUEST_TIMEOUT,
            $scope,
            $scopeId
        );
    }

    public function getScheduledRequestTimeout(int $scopeId = null, string $scope = ScopeInterface::SCOPE_STORE): string
    {
        return (float) $this->scopeConfig->getValue(
            self::XML_PATH_API_SCHEDULED_REQUEST_TIMEOUT,
            $scope,
            $scopeId
        );
    }

    /**
     * @throws ApiException
     * @throws ValidatorException
     */
    public function getJwt($key, ?int $scopeId = null, ?float $timeout = null, string $scope = ScopeInterface::SCOPE_STORE)
    {
        try {
            $authenticationApi = $this->instanceFactory->createApiInstance(
                'authentication',
                new Config(
                    $this->getApiHost($scopeId, $scope),
                    $this->getUserAgent($scopeId, $scope),
                    $timeout,
                )
            );

            $tokenResponse = $authenticationApi->profileLoginUsingPOST(
                new BusinessProfileAuthenticationRequest(['api_key' => $key])
            );

            return $tokenResponse->getToken();
        } catch (ApiException $e) {
            if ($e->getCode() === 401) {
                throw new ValidatorException(
                    __('Profile login failed. Please make sure this a valid, profile scoped api key and try again.')
                );
            } else {
                $this->logger->error('Synerise Api request failed', ['exception' => $e]);
                throw $e;
            }
        }
    }
}