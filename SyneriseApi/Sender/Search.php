<?php

namespace Synerise\Integration\SyneriseApi\Sender;

use Magento\Framework\Exception\ValidatorException;
use Synerise\ItemsSearchApiClient\Api\ListingApi;
use Synerise\ItemsSearchApiClient\Api\SearchApi;
use Synerise\ItemsSearchApiClient\ApiException;
use Synerise\ItemsSearchApiClient\Model\Error;
use Synerise\ItemsSearchApiClient\Model\ListingPostRequest;
use Synerise\ItemsSearchApiClient\Model\ListingResponse;
use Synerise\ItemsSearchApiClient\Model\SearchAutocompleteGet200Response;
use Synerise\ItemsSearchApiClient\Model\SearchAutocompletePostRequest;
use Synerise\ItemsSearchApiClient\Model\SearchFullTextGet200Response;
use Synerise\ItemsSearchApiClient\Model\SearchFullTextPostRequest;

class Search extends AbstractSender
{
    public const API_EXCEPTION = ApiException::class;

    /**
     * Search full text
     *
     * @param int $storeId
     * @param string $indexId
     * @param SearchFullTextPostRequest $request
     * @return SearchFullTextGet200Response|Error
     * @throws ApiException
     * @throws ValidatorException
     */
    public function searchFullText(int $storeId, string $indexId, SearchFullTextPostRequest $request)
    {
        try {
            return $this->sendWithTokenExpiredCatch(
                function () use ($storeId, $indexId, $request) {
                    return $this->getSearchApiInstance($storeId)
                        ->searchFullTextPost($indexId, $request);
                },
                $storeId
            );
        } catch (ApiException $e) {
            $this->logApiException($e);
            throw $e;
        }
    }

    /**
     * Search autocomplete
     *
     * @param int $storeId
     * @param string $indexId
     * @param SearchAutocompletePostRequest $request
     * @return SearchAutocompleteGet200Response|Error
     * @throws ApiException
     * @throws ValidatorException
     */
    public function searchAutocomplete(int $storeId, string $indexId, SearchAutocompletePostRequest $request)
    {
        try {
            return $this->sendWithTokenExpiredCatch(
                function () use ($storeId, $indexId, $request) {
                    return $this->getSearchApiInstance($storeId)
                        ->searchAutocompletePost($indexId, $request);
                },
                $storeId
            );
        } catch (ApiException $e) {
            $this->logApiException($e);
            throw $e;
        }
    }

    /**
     * Get items listing
     *
     * @param int $storeId
     * @param string $indexId
     * @param ListingPostRequest $request
     * @return ListingResponse|Error
     * @throws ApiException
     * @throws ValidatorException
     */
    public function listing(int $storeId, string $indexId, ListingPostRequest $request)
    {
        try {
            return $this->sendWithTokenExpiredCatch(
                function () use ($storeId, $indexId, $request) {
                    return $this->getListingApiInstance($storeId)
                        ->listingPost($indexId, $request);
                },
                $storeId
            );
        } catch (ApiException $e) {
            $this->logApiException($e);
            throw $e;
        }
    }

    /**
     * Get AI Search Api instance
     *
     * @param int $storeId
     * @return SearchApi
     * @throws ValidatorException
     * @throws ApiException
     */
    private function getSearchApiInstance(int $storeId): SearchApi
    {
        return $this->getApiInstance('search', $storeId);
    }

    /**
     * Get AI Listing Api instance
     *
     * @param int $storeId
     * @return ListingApi
     * @throws ValidatorException
     * @throws ApiException
     */
    private function getListingApiInstance(int $storeId): ListingApi
    {
        return $this->getApiInstance('listing', $storeId);
    }
}