<?php

namespace Synerise\Integration\Model\Workspace;

use Ramsey\Uuid\Uuid;
use Zend_Validate_Exception;

class Validator extends \Magento\Framework\Validator\AbstractValidator
{
    public function __construct(
        \Synerise\Integration\Helper\Api $apiHelper
    ) {
        $this->apiHelper = $apiHelper;
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

        $business_profile_authentication_request = new \Synerise\ApiClient\Model\BusinessProfileAuthenticationRequest([
            'api_key' => $apiKey
        ]);

        try {
            $this->apiHelper->getAuthApiInstance()
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
