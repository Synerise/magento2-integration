<?php

namespace Synerise\Integration\SyneriseApi;

use Magento\Framework\Exception\ValidatorException;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\Api\AuthenticationControllerApi;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\BusinessProfileAuthenticationRequest;

class Authentication
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var InstanceFactory
     */
    private $instanceFactory;

    public function __construct(
        LoggerInterface $logger,
        InstanceFactory $instanceFactory
    ) {
        $this->logger = $logger;
        $this->instanceFactory = $instanceFactory;
    }

    /**
     * @param string $apiKey
     * @param Config $config
     * @return string
     * @throws ApiException
     * @throws ValidatorException
     */
    public function getJwt(string $apiKey, Config $config): string
    {
        try {
            $tokenResponse = $this->getAuthenticationApiInstance($config)->profileLoginUsingPOST(
                new BusinessProfileAuthenticationRequest(['api_key' => $apiKey])
            );

            return $tokenResponse->getToken();
        } catch (ApiException $e) {
            $this->logger->error(
                'Synerise Api request failed',
                [
                    'exception' => preg_replace('/ response:.*/s', '', $e->getMessage()),
                    'response_body' => preg_replace('/\n/s', '', $e->getResponseBody())
                ]
            );
            if ($e->getCode() === 401) {
                throw new ValidatorException(
                    __('Profile login failed. Please make sure this a valid, profile scoped api key and try again.')
                );
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param Config $config
     * @return AuthenticationControllerApi
     */
    public function getAuthenticationApiInstance(Config $config): AuthenticationControllerApi
    {
        return $this->instanceFactory->createApiInstance(
            'authentication',
            $config
        );
    }
}