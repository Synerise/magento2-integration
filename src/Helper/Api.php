<?php

namespace Synerise\Integration\Helper;

use \GuzzleHttp\HandlerStack;
use \GuzzleHttp\Middleware;
use \GuzzleHttp\MessageFormatter;
use Loguzz\Middleware\LogMiddleware;
use Synerise\Integration\Loguzz\Formatter\RequestCurlSanitizedFormatter;

class Api extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $authApi;
    protected $bagsApi;
    protected $itemsApi;
    protected $defaultApi;
    protected $apiToken = [];

    /**
     * Api host
     *
     * @return string
     */
    public function getApiHost($storeId = null)
    {
        return $this->scopeConfig->getValue(
            \Synerise\Integration\Helper\Config::XML_PATH_API_HOST,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Api key
     *
     * @return string
     */
    public function getApiKey($storeId = null)
    {
        return $this->scopeConfig->getValue(
            \Synerise\Integration\Helper\Config::XML_PATH_API_KEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isLoggerEnabled($storeId = null)
    {
        return $this->scopeConfig->getValue(
            \Synerise\Integration\Helper\Config::XML_PATH_API_LOGGER_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
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

    public function getAuthApiInstance()
    {
        if (!$this->authApi) {
            $client = new \GuzzleHttp\Client();
            $config = \Synerise\ApiClient\Configuration::getDefaultConfiguration()
                ->setHost(sprintf('%s/v4', $this->getApiHost()));

            $this->authApi = new \Synerise\ApiClient\Api\AuthenticationControllerApi(
                $client,
                $config
            );
        }

        return $this->authApi;
    }

    /**
     * @return \Synerise\ApiClient\Api\DefaultApi
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \Synerise\ApiClient\ApiException
     */
    public function getDefaultApiInstance($storeId = null)
    {
        if (!$this->defaultApi) {
            $client = $this->getGuzzleClient();
            $config = \Synerise\ApiClient\Configuration::getDefaultConfiguration()
                ->setHost(sprintf('%s/v4', $this->getApiHost()))
                ->setAccessToken($this->getApiToken($storeId));

            $this->defaultApi = new \Synerise\ApiClient\Api\DefaultApi(
                $client,
                $config
            );
        }

        return $this->defaultApi;
    }

    /**
     * @return \Synerise\CatalogsApiClient\Api\BagsApi
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \Synerise\ApiClient\ApiException
     */
    public function getBagsApiInstance($storeId)
    {
        if (!$this->bagsApi) {
            $client = $this->getGuzzleClient();
            $config = \Synerise\CatalogsApiClient\Configuration::getDefaultConfiguration()
                ->setHost(sprintf('%s/catalogs', $this->getApiHost()))
                ->setAccessToken($this->getApiToken($storeId));

            $this->bagsApi = new \Synerise\CatalogsApiClient\Api\BagsApi(
                $client,
                $config
            );
        }

        return $this->bagsApi;
    }

    public function getItemsApiInstance($storeId = null)
    {
        if (!$this->itemsApi) {
            $client = $this->getGuzzleClient();
            $config = \Synerise\CatalogsApiClient\Configuration::getDefaultConfiguration()
                ->setHost(sprintf('%s/catalogs', $this->getApiHost()))
                ->setAccessToken($this->getApiToken($storeId));

            $this->itemsApi = new \Synerise\CatalogsApiClient\Api\ItemsApi(
                $client,
                $config
            );
        }

        return $this->itemsApi;
    }

    protected function getApiToken($storeId = null)
    {
        if (!isset($this->apiToken[$storeId])) {
            $authApiInstance = $this->getAuthApiInstance();

            $business_profile_authentication_request = new \Synerise\ApiClient\Model\BusinessProfileAuthenticationRequest([
                'api_key' => $this->getApiKey($storeId)
            ]);

            try {
                $tokenResponse = $authApiInstance->profileLoginUsingPOST($business_profile_authentication_request);
                $this->apiToken[$storeId] = $tokenResponse->getToken();
            } catch (\Synerise\ApiClient\ApiException $e) {
                if ($e->getCode() === 401) {
                    throw new \Magento\Framework\Exception\ValidatorException(
                        __('Test request failed. Please make sure this a valid, profile scoped api key and try again.')
                    );
                } else {
                    $this->_logger->error('Synerise Api request failed', ['exception' => $e]);
                    throw $e;
                }
            }
        }

        return $this->apiToken[$storeId];
    }
}
