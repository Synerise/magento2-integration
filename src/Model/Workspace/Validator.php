<?php

namespace Synerise\Integration\Model\Workspace;

use Ramsey\Uuid\Uuid;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Api\AuthApiFactory;
use Synerise\Integration\Model\ApiConfig;

class Validator extends \Magento\Framework\Validator\AbstractValidator
{
    /**
     * @var Api
     */
    protected $apiHelper;

    /**
     * @var AuthApiFactory
     */
    protected $authApiFactory;

    public function __construct(
        AuthApiFactory $authApiFactory,
        Api $apiHelper
    ) {
        $this->apiHelper = $apiHelper;
        $this->authApiFactory = $authApiFactory;
    }

    public function isValid($workspace)
    {
        $messages = [];

        $apiKey = $workspace->getApiKey();
        if (!Uuid::isValid($apiKey)) {
            $messages['invalid_api_key_format'] = 'Invalid api key format';
        }

        $business_profile_authentication_request = new \Synerise\ApiClient\Model\BusinessProfileAuthenticationRequest([
            'api_key' => $apiKey
        ]);

        try {
            $this->authApiFactory->create(new ApiConfig($this->apiHelper->getApiHost()))
                ->profileLoginUsingPOST($business_profile_authentication_request);
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
