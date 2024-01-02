<?php

namespace Synerise\Integration\Model\Workspace;

use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Validator\AbstractValidator;
use Ramsey\Uuid\Uuid;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Model\Workspace;
use Synerise\Integration\SyneriseApi\Authentication;
use Synerise\Integration\SyneriseApi\ConfigFactory;

class Validator extends AbstractValidator
{

    /**
     * @var ConfigFactory
     */
    private $configFactory;

    /**
     * @var Authentication
     */
    private $authentication;

    /**
     * @param Authentication $authentication
     * @param ConfigFactory $configFactory
     */
    public function __construct(
        Authentication $authentication,
        ConfigFactory $configFactory
    ) {
        $this->authentication = $authentication;
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
                $this->authentication->getJwt(
                    $apiKey,
                    $this->configFactory->createMinimalConfig()
                );
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
