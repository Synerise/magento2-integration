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
    protected $apiToken;

    /**
     * Api key
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->scopeConfig->getValue(
            \Synerise\Integration\Helper\Config::XML_PATH_API_KEY
        );
    }

    public function isLoggerEnabled()
    {
        return $this->scopeConfig->getValue(
            \Synerise\Integration\Helper\Config::XML_PATH_API_LOGGER_ENABLED
        );
    }

    private function getGuzzleClient()
    {
        $options = [];
        if($this->isLoggerEnabled()) {
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
        if(!$this->authApi) {
            $this->authApi = new \Synerise\ApiClient\Api\AuthenticationControllerApi();
        }

        return $this->authApi;
    }

    /**
     * @return \Synerise\ApiClient\Api\DefaultApi
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \Synerise\ApiClient\ApiException
     */
    public function getDefaultApiInstance()
    {
        if(!$this->defaultApi) {
            $client = $this->getGuzzleClient();
            $config = \Synerise\ApiClient\Configuration::getDefaultConfiguration()
                ->setAccessToken($this->getApiToken());

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
    public function getBagsApiInstance()
    {
        if(!$this->bagsApi) {
            $client = $this->getGuzzleClient();
            $config = \Synerise\CatalogsApiClient\Configuration::getDefaultConfiguration()
                ->setAccessToken($this->getApiToken());

            $this->bagsApi = new \Synerise\CatalogsApiClient\Api\BagsApi(
                $client,
                $config
            );
        }

        return $this->bagsApi;
    }

    public function getItemsApiInstance()
    {
        if(!$this->itemsApi) {
            $client = $this->getGuzzleClient();
            $config = \Synerise\CatalogsApiClient\Configuration::getDefaultConfiguration()
                ->setAccessToken($this->getApiToken());

            $this->itemsApi = new \Synerise\CatalogsApiClient\Api\ItemsApi(
                $client,
                $config
            );
        }

        return $this->itemsApi;
    }

    protected function getApiToken()
    {
        if(!$this->apiToken) {
            $authApiInstance = $this->getAuthApiInstance();

            $this->getApiKey();

            $business_profile_authentication_request = new \Synerise\ApiClient\Model\BusinessProfileAuthenticationRequest([
                'api_key' => $this->getApiKey()
            ]);

            try {
                $tokenResponse = $authApiInstance->profileLoginUsingPOST($business_profile_authentication_request);
                $this->apiToken = $tokenResponse->getToken();
            } catch (\Synerise\ApiClient\ApiException $e) {
                if($e->getCode() === 401) {
                    throw new \Magento\Framework\Exception\ValidatorException(
                        __('Test request failed. Please make sure this a valid, profile scoped api key and try again.')
                    );
                } else {
                    $this->_logger->error('Synerise Api request failed', ['exception' => $e]);
                    throw $e;
                }
            }
        }

        return $this->apiToken;
    }

}
