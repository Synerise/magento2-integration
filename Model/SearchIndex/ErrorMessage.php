<?php

namespace Synerise\Integration\Model\SearchIndex;

use Magento\Framework\Exception\ValidatorException;
use Synerise\Integration\Model\SearchIndex;
use Synerise\Integration\SyneriseApi\Sender\Catalog;
use Synerise\ItemsSearchConfigApiClient\ApiException;

class ErrorMessage
{
    /**
     * @var Catalog
     */
    private $catalog;

    public function __construct(Catalog $catalog)
    {
        $this->catalog = $catalog;
    }


    public const ERROR_GENERIC = 'Something went wrong. Please refer to log to find ot more.';

    public const CONFIG_API_ERRORS = [
        '403' => [
            'base_message' => 'API request failed.',
            'url_message' => 'Please make sure the API key has the permissions defined in the <a href="%1">Create an API key</a> section of documentation.',
            'url' => 'https://hub.synerise.com/docs/settings/tool/magento/magento-integration-multistore-support/#create-an-api-key'
        ],
        'SRC-001' => [
            'base_message' => 'AI Engine has not been enabled.',
            'url_message' => 'Please follow the instructions in <a href="%1">Configure AI engine for the catalog in Synerise</a> section in documentation to verify the configuration.',
            'url' => 'https://hub.synerise.com/docs/settings/tool/magento/implementing-ai-search/#configure-ai-engine-for-the-catalog'
        ],
        'SRC-027' => [
            'base_message' => 'AI Engine for the catalog %1 (%2) has not been enabled.',
            'url_message' => 'Please follow the instructions in <a href="%1">Configure AI engine for the catalog in Synerise</a> section in documentation to verify the configuration.',
            'url' => 'https://hub.synerise.com/docs/settings/tool/magento/implementing-ai-search/#configure-ai-engine-for-the-catalog'
        ]
    ];

    /**
     * Get error message based on Exception
     *
     * @param ApiException $e
     * @param SearchIndex|null $searchIndex
     * @return string[]
     */
    public function getMessageFromConfigApiException(ApiException $e, ?SearchIndex $searchIndex = null): array
    {
        if ($e->getResponseBody()) {
            $response = json_decode($e->getResponseBody());
            if (property_exists($response, 'errorCode')) {
                $errorCode = $response->errorCode;
            } else {
                $errorCode = $e->getCode();
            }

            switch ($errorCode) {
                case 'SRC-027':
                    $config = self::CONFIG_API_ERRORS[$errorCode];
                    $catalog = $this->getCatalog($searchIndex);
                    return [
                        'base_message' => __($config['base_message'], $catalog->getId(), $catalog->getName()),
                        'url_message' => $config['url_message'],
                        'url' => $config['url']
                    ];
                default:
                    return self::CONFIG_API_ERRORS[$errorCode] ?? [];
            }
        }

        return [];
    }

    /**
     * Get default error message
     *
     * @return string
     */
    public function getDefaultMessage(): string
    {
        return self::ERROR_GENERIC;
    }

    /**
     * Get catalog by search index
     *
     * @param SearchIndex $searchIndex
     * @return mixed
     * @throws ValidatorException
     * @throws \Synerise\ApiClient\ApiException
     * @throws \Synerise\CatalogsApiClient\ApiException
     */
    protected function getCatalog(SearchIndex $searchIndex)
    {
        return $this->catalog->getCatalogById($searchIndex->getStoreId(), $searchIndex->getItemsCatalogId());
    }
}