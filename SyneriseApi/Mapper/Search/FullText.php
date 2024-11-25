<?php

namespace Synerise\Integration\SyneriseApi\Mapper\Search;

use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Synerise\Integration\Search\SearchRequest\SearchCriteriaBuilder;
use Synerise\ItemsSearchApiClient\Model\SearchFullTextPostRequest;

class FullText
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
     * @return SearchFullTextPostRequest
     */
    public function prepareRequest(SearchCriteriaInterface $searchCriteria, ?string $uuid = null): SearchFullTextPostRequest
    {
        $criteria = $this->searchCriteriaBuilder->build($searchCriteria);

        return new SearchFullTextPostRequest([
            'client_uuid' => $uuid,
            'query' => $criteria->getQuery(),
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