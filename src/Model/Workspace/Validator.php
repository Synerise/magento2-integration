<?php

namespace Synerise\Integration\Model\Workspace;

use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Validator\AbstractValidator;
use Ramsey\Uuid\Uuid;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Model\Workspace;
use Synerise\Integration\SyneriseApi\AuthenticatorFactory;
use Synerise\Integration\SyneriseApi\Config as ApiConfig;
use Synerise\Integration\SyneriseApi\ConfigFactory as ApiConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory as ApiInstanceFactory;

class Validator extends AbstractValidator
{

    /**
     * @var AuthenticatorFactory
     */
    private $authenticatorFactory;

    /**
     * @var ApiInstanceFactory
     */
    private $apiInstanceFactory;

    /**
     * @var ApiConfigFactory
     */
    private $apiConfigFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param AuthenticatorFactory $authenticatorFactory
     * @param ApiInstanceFactory $apiInstanceFactory
     * @param ApiConfigFactory $apiConfigFactory
     * @param Logger $logger
     */
    public function __construct(
        AuthenticatorFactory $authenticatorFactory,
        ApiInstanceFactory $apiInstanceFactory,
        ApiConfigFactory $apiConfigFactory,
        Logger $logger
    ) {
        $this->authenticatorFactory = $authenticatorFactory;
        $this->apiInstanceFactory = $apiInstanceFactory;
        $this->apiConfigFactory = $apiConfigFactory;
        $this->logger = $logger;
    }

    /**
     * Validate workspace
     *
     * @param Workspace $workspace
     * @return bool
     * @throws ApiException|ValidatorException
     */
    public function isValid($workspace): bool
    {
        $messages = [];

        $apiConfig = $this->apiConfigFactory->create();
        $apiKey = $workspace->getApiKey();
        if (!Uuid::isValid($apiKey)) {
            $messages['invalid_api_key_format'] = 'Invalid api key format';
        } else {
            try {
                $this->authenticatorFactory->create($apiConfig)->getAccessToken($apiKey);
            } catch (ValidatorException $e) {
                $messages['invalid_api_key'] = $e->getMessage();
            }
        }

        $guid = $workspace->getGuid();
        if ($guid) {
            if (!Uuid::isValid($guid)) {
                $messages['invalid_guid_format'] = 'Invalid guid format';
            } else {
                try {
                    $apiConfig->setAuthorizationType(ApiConfig::AUTHORIZATION_TYPE_BASIC);
                    $this->apiInstanceFactory->createApiInstance('apiKey', $apiConfig, $workspace)
                        ->checkPermissions(Workspace::REQUIRED_PERMISSIONS);
                } catch (ApiException $e) {
                    $this->logger->getLogger()->error(
                        'Synerise Api request failed',
                        [
                            'exception' => preg_replace('/ response:.*/s', '', $e->getMessage()),
                            'response_body' => preg_replace('/\n/s', '', (string) $e->getResponseBody())
                        ]
                    );
                    if ($e->getCode() === 401) {
                        $messages['invalid_api_key'] = __('Basic authentication failed. 
                            Please make sure this a valid GUID and try again.');
                    } else {
                        throw $e;
                    }
                }
            }
        }

        $this->_addMessages($messages);

        return empty($messages);
    }
}
