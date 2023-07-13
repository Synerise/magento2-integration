<?php

namespace Synerise\Integration\Helper;

use \GuzzleHttp\HandlerStack;
use Loguzz\Middleware\LogMiddleware;
use Magento\Store\Model\ScopeInterface;
use Synerise\Integration\Loguzz\Formatter\RequestCurlSanitizedFormatter;
use Synerise\Integration\Model\Config\Backend\Workspace;

class Api extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_API_HOST = 'synerise/api/host';

    const XML_PATH_API_KEY = 'synerise/api/key';

    const XML_PATH_API_LOGGER_ENABLED = 'synerise/api/logger_enabled';

    const XML_PATH_API_KEEP_ALIVE_ENABLED = 'synerise/api/keep_alive_enabled';

    const XML_PATH_API_BASIC_AUTH_ENABLED = 'synerise/api/basic_auth_enabled';

    const XML_PATH_API_SCHEDULED_REQUEST_TIMEOUT = 'synerise/api/scheduled_request_timeout';

    const XML_PATH_API_LIVE_REQUEST_TIMEOUT = 'synerise/api/live_request_timeout';

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
    public function getApiKey($scope, $scopeId)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_API_KEY,
            $scope,
            $scopeId
        );
    }

    /**
     * Guid & api key based token
     *
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getBasicToken($scope, $scopeId)
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
        return (boolean) $this->getApiKey($scope, $scopeId);
    }

    public function isLoggerEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_API_LOGGER_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isKeepAliveEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_API_KEEP_ALIVE_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isBaiscAuthEnabled($scope = ScopeInterface::SCOPE_STORE, $scopeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_API_BASIC_AUTH_ENABLED,
            $scope,
            $scopeId
        );
    }

    public function getLiveRequestTimeout($scopeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return (float) $this->scopeConfig->getValue(
            self::XML_PATH_API_LIVE_REQUEST_TIMEOUT,
            $scope,
            $scopeId
        );
    }

    public function getScheduledRequestTimeout($scopeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return (float) $this->scopeConfig->getValue(
            self::XML_PATH_API_SCHEDULED_REQUEST_TIMEOUT,
            $scope,
            $scopeId
        );
    }

    private function getGuzzleClient($timeout, $basicToken = null)
    {
        $options = [
            'connect_timeout' => $timeout,
            'timeout' => $timeout
        ];

        if ($this->isKeepAliveEnabled()) {
            $options['headers'] = [
                'Connection' => [ 'keep-alive' ]
            ];
        }


        if ($basicToken) {
            $options['headers']['Authorization'] = [ "Basic {$basicToken}" ];
        }

        if ($this->isLoggerEnabled()) {
            $LogMiddleware = new LogMiddleware(
                $this->_logger,
                ['request_formatter' => new RequestCurlSanitizedFormatter()]
            );

            $handlerStack = HandlerStack::create();
            $handlerStack->push($LogMiddleware, 'logger');
            $options['handler'] =  $handlerStack;
        }

        return new \GuzzleHttp\Client($options);
    }

    public function getAuthApiInstance($scope = ScopeInterface::SCOPE_STORE, $scopeId = null, $timeout = null)
    {
        $key = md5(serialize(func_get_args()));
        if (!isset($this->authApi[$key])) {
            if (!$timeout) {
                $timeout = $this->getLiveRequestTimeout($scopeId);
            }
            $client = new \GuzzleHttp\Client([
                'timeout' => $timeout,
                'connect_timeout' => $timeout
            ]);
            $config = clone \Synerise\ApiClient\Configuration::getDefaultConfiguration()
                ->setHost(sprintf('%s/v4', $this->getApiHost($scope, $scopeId)));

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
    public function getDefaultApiInstance($storeId = null, $timeout = null)
    {
        if (!isset($this->defaultApi[(int) $storeId])) {
            if (!$timeout) {
                $timeout = $this->getLiveRequestTimeout($storeId);
            }

            $basicToken = $this->isBaiscAuthEnabled(ScopeInterface::SCOPE_STORE, $storeId) ?
                $this->getBasicToken(ScopeInterface::SCOPE_STORE, $storeId) : null;

            $client = $this->getGuzzleClient($timeout, $basicToken);
            $config = clone \Synerise\ApiClient\Configuration::getDefaultConfiguration()
                ->setHost(sprintf('%s/v4', $this->getApiHost(ScopeInterface::SCOPE_STORE, $storeId)));

            if (!empty($basicToken)) {
                $config->setAccessToken(null);
            } else {
                $config->setAccessToken($this->getJwt(ScopeInterface::SCOPE_STORE, $storeId, $timeout));
            }

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
    public function getBagsApiInstance($storeId, $timeout = null)
    {
        if (!isset($this->bagsApi[$storeId])) {
            if (!$timeout) {
                $timeout = $this->getLiveRequestTimeout($storeId);
            }
            $client = $this->getGuzzleClient($timeout);
            $config = \Synerise\CatalogsApiClient\Configuration::getDefaultConfiguration()
                ->setHost(sprintf('%s/catalogs', $this->getApiHost(ScopeInterface::SCOPE_STORE, $storeId)))
                ->setAccessToken($this->getJwt(ScopeInterface::SCOPE_STORE, $storeId));

            $this->bagsApi[$storeId] = new \Synerise\CatalogsApiClient\Api\BagsApi(
                $client,
                $config
            );
        }

        return $this->bagsApi[$storeId];
    }

    public function getItemsApiInstance($storeId, $timeout = null)
    {
        if (!isset($this->itemsApi[$storeId])) {
            if (!$timeout) {
                $timeout = $this->getLiveRequestTimeout($storeId);
            }
            $client = $this->getGuzzleClient($timeout);
            $config = \Synerise\CatalogsApiClient\Configuration::getDefaultConfiguration()
                ->setHost(sprintf('%s/catalogs', $this->getApiHost(ScopeInterface::SCOPE_STORE, $storeId)))
                ->setAccessToken($this->getJwt(ScopeInterface::SCOPE_STORE, $storeId));

            $this->itemsApi[$storeId] = new \Synerise\CatalogsApiClient\Api\ItemsApi(
                $client,
                $config
            );
        }

        return $this->itemsApi[$storeId];
    }

    public function getTrackerApiInstance($scope, $scopeId, $token = null, $timeout = null)
    {
        $key = md5(serialize(func_get_args()));
        if (!isset($this->trackerApi[$key])) {
            if (!$token) {
                $token = $this->getJwt($scope, $scopeId);
            }
            if (!$timeout) {
                $timeout = $this->getLiveRequestTimeout($scopeId, $scope);
            }
            $client = $this->getGuzzleClient($timeout);
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

    public function getApiKeyApiInstance($scope, $scopeId, $token = null, $timeout = null)
    {
        $key = md5(serialize(func_get_args()));
        if (!isset($this->apiKeyApi[$key])) {
            if (!$token) {
                $token = $this->getJwt($scope, $scopeId);
            }
            if (!$timeout) {
                $timeout = $this->getLiveRequestTimeout($scopeId, $scope);
            }
            $client = $this->getGuzzleClient($timeout);
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

    public function getJwt($scope, $scopeId, $timeout = null, $key = null)
    {
        $key = $key ?: $this->getApiKey($scope, $scopeId);
        if (!isset($this->apiToken[$key])) {
            $business_profile_authentication_request = new \Synerise\ApiClient\Model\BusinessProfileAuthenticationRequest([
                'api_key' => $key
            ]);

            try {
                $tokenResponse = $this->getAuthApiInstance($scope, $scopeId, $timeout)
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
