<?php

namespace Synerise\Integration\SyneriseApi\Mapper\Search;

use Synerise\Integration\Search\SearchRequest\Criteria;
use Synerise\ItemsSearchApiClient\Model\ListingPostRequest;

class Listing
{
    /**
     * Prepare request
     *
     * @param Criteria $criteria
     * @param string|null $uuid
     * @return ListingPostRequest
     */
    public function prepareRequest(
        Criteria $criteria,
        ?string $uuid = null
    ): ListingPostRequest
    {
        return new ListingPostRequest([
            'client_uuid' => $uuid,
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