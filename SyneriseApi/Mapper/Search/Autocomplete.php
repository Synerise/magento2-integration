<?php

namespace Synerise\Integration\SyneriseApi\Mapper\Search;

use Synerise\Integration\Search\SearchRequest\Filters;
use Synerise\ItemsSearchApiClient\Model\SearchAutocompletePostRequest;

class Autocomplete
{
    /**
     * Prepare request
     *
     * @param string $query
     * @param int $limit
     * @param string|null $uuid
     * @param Filters|null $filters
     * @return SearchAutocompletePostRequest
     */
    public function prepareRequest(
        string $query,
        int $limit = 8,
        ?string $uuid = null,
        ?Filters $filters = null
    ): SearchAutocompletePostRequest
    {
        return new SearchAutocompletePostRequest([
            'client_uuid' => $uuid,
            'query' =>  $query,
            'limit' => $limit,
            'filters' => $filters ? (string) $filters : null,
            'include_meta' => true
        ]);
    }
}