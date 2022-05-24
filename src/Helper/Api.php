<?php

namespace Synerise\Integration\Helper;

use \GuzzleHttp\HandlerStack;
use \GuzzleHttp\Middleware;
use \GuzzleHttp\MessageFormatter;
use Loguzz\Middleware\LogMiddleware;
use Magento\Store\Model\ScopeInterface;
use Synerise\Integration\Loguzz\Formatter\RequestCurlSanitizedFormatter;

class Api extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $authApi = [];
    protected $bagsApi = [];
    protected $itemsApi = [];
    protected $defaultApi = [];
    protected $trackerApi = [];
    protected $apiToken = [];

    /**
     * Api host
     *
     * @return string
     */
    public function getApiHost($scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        return $this->scopeConfig->getValue(
            \Synerise\Integration\Helper\Config::XML_PATH_API_HOST,
            $scope,
            $scopeId
        );
    }

    /**
     * Api key
     *
     * @return string
     */
    public function getApiKey($scope, $scopeId)
    {
        return $this->scopeConfig->getValue(
            \Synerise\Integration\Helper\Config::XML_PATH_API_KEY, $scope, $scopeId
        );
    }

    public function isLoggerEnabled($storeId = null)
    {
        return $this->scopeConfig->getValue(
            \Synerise\Integration\Helper\Config::XML_PATH_API_LOGGER_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    private function getGuzzleClient()
    {
        $options = [];
        if ($this->isLoggerEnabled()) {
            $LogMiddleware = new LogMiddleware(
                $this->_logger,
                ['request_formatter' => new RequestCurlSanitizedFormatter()]
            );

            $handlerStack = HandlerStack::create();
            $handlerStack->push($LogMiddleware, 'logger');
            $options = [
                'handler' => $handlerStack
            ];
        }

        return new \GuzzleHttp\Client($options);
    }

    public function getAuthApiInstance($scope = ScopeInterface::SCOPE_STORE, $scopeId = null, $token = null)
    {
        $key = md5(serialize(func_get_args()));
        if (!isset($this->authApi[$key])) {
            $client = new \GuzzleHttp\Client();
            $config = clone \Synerise\ApiClient\Configuration::getDefaultConfiguration()
                ->setHost(sprintf('%s/v4', $this->getApiHost($scope, $scopeId)));

            if ($token) {
                $config->setAccessToken($token);
            }

            $this->authApi[$key] = new \Synerise\ApiClient\Api\AuthenticationControllerApi(
                $client,
                $config
            );
        }

        return $this->authApi[$key];
    }

    /**
     * @return \Synerise\ApiClient\Api\DefaultApi
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \Synerise\ApiClient\ApiException
     */
    public function getDefaultApiInstance($storeId = null)
    {
        if (!isset($this->defaultApi[(int) $storeId])) {
            $client = $this->getGuzzleClient();
            $config = clone \Synerise\ApiClient\Configuration::getDefaultConfiguration()
                ->setHost(sprintf('%s/v4', $this->getApiHost(ScopeInterface::SCOPE_STORE, $storeId)))
                ->setAccessToken($this->getApiToken(ScopeInterface::SCOPE_STORE, $storeId));

            $this->defaultApi[(int) $storeId] = new \Synerise\ApiClient\Api\DefaultApi(
                $client,
                $config
            );
        }

        return $this->defaultApi[(int) $storeId];
    }

    /**
     * @return \Synerise\CatalogsApiClient\Api\BagsApi
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \Synerise\ApiClient\ApiException
     */
    public function getBagsApiInstance($storeId)
    {
        if (!isset($this->bagsApi[$storeId])) {
            $client = $this->getGuzzleClient();
            $config = \Synerise\CatalogsApiClient\Configuration::getDefaultConfiguration()
                ->setHost(sprintf('%s/catalogs', $this->getApiHost(ScopeInterface::SCOPE_STORE, $storeId)))
                ->setAccessToken($this->getApiToken(ScopeInterface::SCOPE_STORE, $storeId));

            $this->bagsApi[$storeId] = new \Synerise\CatalogsApiClient\Api\BagsApi(
                $client,
                $config
            );
        }

        return $this->bagsApi[$storeId];
    }

    public function getItemsApiInstance($storeId)
    {
        if (!isset($this->itemsApi[$storeId])) {
            $client = $this->getGuzzleClient();
            $config = \Synerise\CatalogsApiClient\Configuration::getDefaultConfiguration()
                ->setHost(sprintf('%s/catalogs', $this->getApiHost(ScopeInterface::SCOPE_STORE, $storeId)))
                ->setAccessToken($this->getApiToken(ScopeInterface::SCOPE_STORE, $storeId));

            $this->itemsApi[$storeId] = new \Synerise\CatalogsApiClient\Api\ItemsApi(
                $client,
                $config
            );
        }

        return $this->itemsApi[$storeId];
    }

    public function getTrackerApiInstance($scope, $scopeId, $token = null)
    {
        $key = md5(serialize(func_get_args()));
        if (!isset($this->trackerApi[$key])) {
            if (!$token) {
                $token = $this->getApiToken($scope, $scopeId);
            }
            $client = $this->getGuzzleClient();
            $config = clone \Synerise\ApiClient\Configuration::getDefaultConfiguration()
                ->setHost(sprintf('%s/business-profile-service', $this->getApiHost($scope, $scopeId)))
                ->setAccessToken($token);

            $this->trackerApi[$key] = new \Synerise\ApiClient\Api\TrackerControllerApi(
                $client,
                $config
            );
        }

        return $this->trackerApi[$key];
    }

    public function getApiKeyApiInstance($scope, $scopeId, $token = null)
    {
        $key = md5(serialize(func_get_args()));
        if (!isset($this->apiKeyApi[$key])) {
            if (!$token) {
                $token = $this->getApiToken($scope, $scopeId);
            }
            $client = $this->getGuzzleClient();
            $config = clone \Synerise\ApiClient\Configuration::getDefaultConfiguration()
                ->setHost(sprintf('%s/uauth', $this->getApiHost($scope, $scopeId)))
                ->setAccessToken($token);

            $this->apiKeyApi[$key] = new \Synerise\ApiClient\Api\ApiKeyControllerApi(
                $client,
                $config
            );
        }

        return $this->apiKeyApi[$key];
    }

    public function getApiToken($scope, $scopeId, $key = null)
    {
        $key = $key ?: $this->getApiKey($scope, $scopeId);
        if (!isset($this->apiToken[$key])) {
            $business_profile_authentication_request = new \Synerise\ApiClient\Model\BusinessProfileAuthenticationRequest([
                'api_key' => $key
            ]);

            try {
                $tokenResponse = $this->getAuthApiInstance($scope, $scopeId)
                    ->profileLoginUsingPOST($business_profile_authentication_request);
                $this->apiToken[$key] = $tokenResponse->getToken();
            } catch (\Synerise\ApiClient\ApiException $e) {
                if ($e->getCode() === 401) {
                    throw new \Magento\Framework\Exception\ValidatorException(
                        __('Profile login failed. Please make sure this a valid, profile scoped api key and try again.')
                    );
                } else {
                    $this->_logger->error('Synerise Api request failed', ['exception' => $e]);
                    throw $e;
                }
            }
        }

        return $this->apiToken[$key];
    }
}
