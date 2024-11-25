<?php

namespace Synerise\Integration\SyneriseApi\Mapper\Search;

use Synerise\Integration\Search\SearchRequest\SearchCriteriaBuilder;
use Synerise\ItemsSearchApiClient\Model\SearchAutocompletePostRequest;

class Autocomplete
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Prepare request
     *
     * @param string $query
     * @param int $limit
     * @param string|null $uuid
     * @return SearchAutocompletePostRequest
     */
    public function prepareRequest(string $query, int $limit = 5, ?string $uuid = null): SearchAutocompletePostRequest
    {
        $criteria = $this->searchCriteriaBuilder->build();

        return new SearchAutocompletePostRequest([
            'client_uuid' => $uuid,
            'limit' => $limit,
            'query' =>  $query,
            'filters' => $criteria->getFilters(),
            'include_meta' => true
        ]);
    }
}