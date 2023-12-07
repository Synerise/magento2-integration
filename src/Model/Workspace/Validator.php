<?php

namespace Synerise\Integration\Model\Workspace;

use Ramsey\Uuid\Uuid;
use Synerise\ApiClient\Model\BusinessProfileAuthenticationRequest;
use Synerise\Integration\SyneriseApi\Config;
use Synerise\Integration\SyneriseApi\ConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory;

class Validator extends \Magento\Framework\Validator\AbstractValidator
{

    /**
     * @var ConfigFactory
     */
    private $configFactory;

    /**
     * @var InstanceFactory
     */
    private $apiInstanceFactory;

    public function __construct(
        ConfigFactory $configFactory,
        InstanceFactory $apiInstanceFactory
    ) {
        $this->configFactory = $configFactory;
        $this->apiInstanceFactory = $apiInstanceFactory;
    }

    public function isValid($workspace)
    {
        $messages = [];

        $apiKey = $workspace->getApiKey();
        if (!Uuid::isValid($apiKey)) {
            $messages['invalid_api_key_format'] = 'Invalid api key format';
        }

        $guid = $workspace->getGuid();
        if ($guid && !Uuid::isValid($guid)) {
            $messages['invalid_guid_format'] = 'Invalid guid format';
        }

        try {
            $authenticationApi = $this->apiInstanceFactory->createApiInstance(
                'authentication',
                new Config(
                    $this->configFactory->getApiHost(),
                    $this->configFactory->getUserAgent(),
                    $this->configFactory->getLiveRequestTimeout()
                )
            );

            $authenticationApi->profileLoginUsingPOST(
                new BusinessProfileAuthenticationRequest(['api_key' => $apiKey])
            );
        } catch (\Synerise\ApiClient\ApiException $e) {
            if ($e->getCode() === 401) {
                throw new \Magento\Framework\Exception\ValidatorException(
                    __('Test request failed. Please make sure this a valid, profile scoped api key and try again.')
                );
            } else {
                throw $e;
            }
        }

        $this->_addMessages($messages);

        return empty($messages);
    }
}
