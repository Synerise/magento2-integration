<?php

namespace Synerise\Integration\Model\Workspace;

use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Validator\AbstractValidator;
use Ramsey\Uuid\Uuid;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Model\Workspace;
use Synerise\Integration\SyneriseApi\AuthenticatorFactory;
use Synerise\Integration\SyneriseApi\ConfigFactory;

class Validator extends AbstractValidator
{

    /**
     * @var ConfigFactory
     */
    private $configFactory;

    /**
     * @var AuthenticatorFactory
     */
    private $authenticatorFactory;

    /**
     * @param AuthenticatorFactory $authenticatorFactory
     * @param ConfigFactory $configFactory
     */
    public function __construct(
        AuthenticatorFactory $authenticatorFactory,
        ConfigFactory $configFactory
    ) {
        $this->authenticatorFactory = $authenticatorFactory;
        $this->configFactory = $configFactory;
    }

    /**
     * Validate workspace
     *
     * @param Workspace $workspace
     * @return bool
     * @throws ApiException
     */
    public function isValid($workspace): bool
    {
        $messages = [];

        $apiKey = $workspace->getApiKey();
        if (!Uuid::isValid($apiKey)) {
            $messages['invalid_api_key_format'] = 'Invalid api key format';
        } else {
            try {
                $this->authenticatorFactory->create($this->configFactory->create())
                    ->getAccessToken($apiKey);
            } catch (ValidatorException $e) {
                $messages['invalid_api_key'] = $e->getMessage();
            }
        }

        $guid = $workspace->getGuid();
        if ($guid && !Uuid::isValid($guid)) {
            $messages['invalid_guid_format'] = 'Invalid guid format';
        }

        $this->_addMessages($messages);

        return empty($messages);
    }
}
