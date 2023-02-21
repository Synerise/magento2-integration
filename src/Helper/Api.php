<?php

namespace Synerise\Integration\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\BusinessProfileAuthenticationRequest;
use Synerise\ApiClient\Model\TokenResponse;
use Synerise\Integration\Helper\Api\Factory\AuthApiFactory;
use Synerise\Integration\Model\ApiConfig;

class Api
{
    const XML_PATH_API_HOST = 'synerise/api/host';
    
    const XML_PATH_API_KEY = 'synerise/api/key';
    
    const XML_PATH_API_LOGGER_ENABLED = 'synerise/api/logger_enabled';

    /**
     * @var ApiConfig[]
     */
    protected $ApiConfigs;

    /**
     * @var string[]
     */
    protected $apiTokens = [];

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var AuthApiFactory
     */
    private $authApiFactory;

    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        AuthApiFactory $authApiFactory
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->authApiFactory = $authApiFactory;
    }

    /**
     * @param int|null $scopeId
     * @param string|null $scope
     * @return ApiConfig
     * @throws ApiException|ValidatorException
     */
    public function getApiConfigByScope(?int $scopeId = null, ?string $scope = ScopeInterface::SCOPE_STORE): ApiConfig
    {
        $key = $scope.$scopeId;
        if (!isset($this->ApiConfigs[$key])) {
            $apiKey = $this->getApiKey($scopeId, $scope);
            $apiHost = $this->getApiHost($scopeId, $scope);
            $token = $this->getApiToken($apiKey, $apiHost);

            $this->ApiConfigs[$key] = new ApiConfig($apiHost, $token, $this->isLoggerEnabled());
        }

        return $this->ApiConfigs[$key];
    }

    /**
     * @param string $apiKey
     * @param int|null $scopeId
     * @param string|null $scope
     * @return ApiConfig
     * @throws ValidatorException
     * @throws ApiException
     */
    public function getApiConfigByApiKey(
        string $apiKey,
        ?int $scopeId = null,
        ?string $scope = ScopeInterface::SCOPE_STORE
    ): ApiConfig
    {
        $apiHost = $this->getApiHost($scopeId, $scope);
        $token = $this->getApiToken($apiKey, $apiHost);

        return new ApiConfig($apiHost, $token);
    }

    /**
     * Api host
     *
     * @param int|null $scopeId
     * @param string|null $scope
     * @return string
     */
    public function getApiHost(?int $scopeId = null, ?string $scope = ScopeInterface::SCOPE_STORE): string
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
     * @param int|null $scopeId
     * @param string|null $scope
     * @return string
     */
    public function getApiKey(?int $scopeId = null, ?string $scope = ScopeInterface::SCOPE_STORE): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_API_KEY,
            $scope,
            $scopeId
        );
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function isLoggerEnabled($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_API_LOGGER_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param string $apiKey
     * @param string $apiHost
     * @return string
     * @throws ValidatorException
     * @throws ApiException
     */
    public function getApiToken(string $apiKey, string $apiHost): string
    {
        if (!isset($this->apiTokens[$apiKey])) {
            $apiConfig = new ApiConfig($apiHost);
            $request = new BusinessProfileAuthenticationRequest([
                'api_key' => $apiKey
            ]);
            $this->apiTokens[$apiKey] = $this->profileLogin($apiConfig, $request)->getToken();
        }

        return $this->apiTokens[$apiKey];
    }

    /**
     * @param ApiConfig $apiConfig
     * @param BusinessProfileAuthenticationRequest $request
     * @return TokenResponse
     * @throws ValidatorException
     * @throws ApiException
     */
    public function profileLogin(ApiConfig $apiConfig, BusinessProfileAuthenticationRequest $request): TokenResponse
    {
        try {
            return $this->authApiFactory->get($apiConfig)->profileLoginUsingPOST($request);
        } catch (ApiException $e) {
            if ($e->getCode() === 401) {
                throw new ValidatorException(
                    __('Login failed. Please make sure this a valid, workspace scoped api key and try again.')
                );
            } else {
                $this->logger->error('Synerise Api request failed', ['exception' => $e]);
                throw $e;
            }
        }
    }
}
