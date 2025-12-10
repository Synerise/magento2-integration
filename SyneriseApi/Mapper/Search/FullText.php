<?php

namespace Synerise\Integration\SyneriseApi\Mapper\Search;

use Synerise\Integration\Search\SearchRequest\Criteria;
use Synerise\ItemsSearchApiClient\Model\SearchFullTextPostRequest;

class FullText
{
    /**
     * Prepare request
     *
     * @param Criteria $criteria
     * @param string|null $uuid
     * @param string|null $correlationId
     * @return SearchFullTextPostRequest
     */
    public function prepareRequest(
        Criteria $criteria,
        ?string  $uuid = null,
        ?string  $correlationId = null
    ): SearchFullTextPostRequest
    {
        return new SearchFullTextPostRequest([
            'client_uuid' => $uuid,
            'correlation_id' => $correlationId,
            'query' => $criteria->getQuery(),
            'page' => $criteria->getPage(),
            'limit' => $criteria->getLimit(),
            'facets' => $criteria->getFacets(),
            'sort_by' => $criteria->getSortBy(),
            'ordering' => $criteria->getOrdering(),
            'filters' => $criteria->getFilters() ? (string) $criteria->getFilters() : null,
            'include_meta' => true
        ]);
    }
}