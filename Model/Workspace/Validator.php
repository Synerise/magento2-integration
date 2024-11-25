<?php

namespace Synerise\Integration\Model\Workspace;

use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Validator\AbstractValidator;
use Ramsey\Uuid\Uuid;
use Synerise\ApiClient\Api\ApiKeyControllerApi;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\ApiKeyPermissionCheckResponse;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Model\Workspace;
use Synerise\Integration\SyneriseApi\ConfigFactory as ApiConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory as ApiInstanceFactory;

class Validator extends AbstractValidator
{
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
     * @param ApiInstanceFactory $apiInstanceFactory
     * @param ApiConfigFactory $apiConfigFactory
     * @param Logger $logger
     */
    public function __construct(
        ApiInstanceFactory $apiInstanceFactory,
        ApiConfigFactory $apiConfigFactory,
        Logger $logger
    ) {
        $this->apiInstanceFactory = $apiInstanceFactory;
        $this->apiConfigFactory = $apiConfigFactory;
        $this->logger = $logger;
    }

    /**
     * Validate workspace
     *
     * @param Workspace $workspace
     * @return bool
     */
    public function isValid($workspace): bool
    {
        $messages = [];

        if (!Uuid::isValid($workspace->getApiKey())) {
            $messages['invalid_api_key_format'] = 'Invalid api key format';
        }

        $guid = $workspace->getGuid();
        if ($guid && !Uuid::isValid($guid)) {
            $messages['invalid_guid_format'] = 'Invalid guid format';
        }

        $this->_addMessages($messages);

        return empty($messages);
    }

    /**
     * Check permissions
     *
     * @param Workspace $workspace
     * @return ApiKeyPermissionCheckResponse
     * @throws ApiException|ValidatorException
     */
    public function checkPermissions(Workspace $workspace): ApiKeyPermissionCheckResponse
    {
        try {
            return $this->createApiKeyInstance($workspace)
                ->checkPermissions(Workspace::REQUIRED_PERMISSIONS);
        } catch (ApiException $e) {
            $this->logger->error(
                'Synerise Api request failed',
                [
                    'exception' => preg_replace('/ response:.*/s', '', $e->getMessage()),
                    'response_body' => preg_replace('/\n/s', '', (string) $e->getResponseBody())
                ]
            );
            if ($e->getCode() === 401) {
                throw new ValidatorException(__('Basic authentication failed. 
                    Please make sure this is a valid GUID and try again.'));
            } else {
                throw $e;
            }
        }
    }

    /**
     * Create API key instance
     *
     * @param Workspace $workspace
     * @return ApiKeyControllerApi
     * @throws ApiException|ValidatorException
     */
    protected function createApiKeyInstance(Workspace $workspace): ApiKeyControllerApi
    {
        return $this->apiInstanceFactory->createApiInstance(
            'apiKey',
            $this->apiConfigFactory->create(),
            $workspace
        );
    }
}
