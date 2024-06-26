<?php

namespace Synerise\Integration\SyneriseApi;

use GuzzleHttp\Client;
use Magento\Framework\Exception\ValidatorException;
use Synerise\ApiClient\Api\AuthenticationControllerApi;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Configuration;
use Synerise\ApiClient\Model\BusinessProfileAuthenticationRequest;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Model\WorkspaceInterface;
use Synerise\Integration\SyneriseApi\Config as ApiConfig;

class Authenticator
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ApiConfig
     */
    private $apiConfig;

    /**
     * @var WorkspaceInterface
     */
    private $workspace;

    /**
     * @param Logger $logger
     * @param ApiConfig $apiConfig
     * @param WorkspaceInterface $workspace
     */
    public function __construct(
        Logger $logger,
        ApiConfig $apiConfig,
        WorkspaceInterface $workspace
    ) {
        $this->logger = $logger;
        $this->apiConfig = $apiConfig;
        $this->workspace = $workspace;
    }

    /**
     * Get JWT by profile login request
     *
     * @param string $apiKey
     * @return string
     * @throws ApiException
     * @throws ValidatorException
     */
    public function getAccessToken(string $apiKey): string
    {
        try {
            $tokenResponse = $this->createAuthenticationApiInstance()->profileLoginUsingPOST(
                new BusinessProfileAuthenticationRequest(['api_key' => $apiKey])
            );

            return $tokenResponse->getToken();
        } catch (ApiException $e) {
            $this->logger->error(
                'Synerise Api request failed',
                [
                    'exception' => preg_replace('/ response:.*/s', '', $e->getMessage()),
                    'response_body' => preg_replace('/\n/s', '', (string) $e->getResponseBody())
                ]
            );
            if ($e->getCode() === 401) {
                throw new ValidatorException(
                    __('Workspace login failed. 
                    Please make sure this is a valid api key of type `workspace` and try again.')
                );
            }

            throw $e;
        }
    }

    /**
     * Create Authentication API instance
     *
     * @return AuthenticationControllerApi
     */
    public function createAuthenticationApiInstance(): AuthenticationControllerApi
    {
        return new AuthenticationControllerApi(
            new Client([
                'connect_timeout' => $this->apiConfig->getTimeout(),
                'timeout' => $this->apiConfig->getTimeout(),
                'headers' => $this->apiConfig->isKeepAliveEnabled() ? ['Connection' => [ 'keep-alive' ]] : []
            ]),
            clone Configuration::getDefaultConfiguration()
                ->setUserAgent($this->apiConfig->getUserAgent())
                ->setHost(sprintf('%s/v4', $this->workspace->getApiHost()))
        );
    }
}
