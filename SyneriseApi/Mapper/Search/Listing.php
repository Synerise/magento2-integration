<?php

namespace Synerise\Integration\SyneriseApi\Mapper\Search;

use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Synerise\Integration\Search\SearchRequest\SearchCriteriaBuilder;
use Synerise\ItemsSearchApiClient\Model\ListingPostRequest;

class Listing
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
     * @param SearchCriteriaInterface $searchCriteria
     * @param string|null $uuid
     * @return ListingPostRequest
     */
    public function prepareRequest(SearchCriteriaInterface $searchCriteria, ?string $uuid = null): ListingPostRequest
    {
        $criteria = $this->searchCriteriaBuilder->build($searchCriteria);

        return new ListingPostRequest([
            'client_uuid' => $uuid,
            'page' => $criteria->getPage(),
            'limit' => $criteria->getLimit(),
            'facets' => $criteria->getFacets(),
            'sort_by' => $criteria->getSortBy(),
            'ordering' => $criteria->getOrdering(),
            'filters' => $criteria->getFilters(),
            'include_meta' => true
        ]);
    }
}