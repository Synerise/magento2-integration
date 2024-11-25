<?php

namespace Synerise\Integration\Search;

use Magento\Framework\Api\Search\Document;
use Magento\Framework\Api\Search\DocumentInterface;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\Api\Search\SearchResultFactory;
use Magento\Framework\Api\Search\SearchResultInterface;
use Synerise\Integration\Search\SearchResponse\AggregationBuilder;
use Synerise\ItemsSearchApiClient\Model\Error;
use Synerise\ItemsSearchApiClient\Model\ListingResponse;
use Synerise\ItemsSearchApiClient\Model\ModelInterface;
use Synerise\ItemsSearchApiClient\Model\SearchFullTextGet200Response;

class SearchResponseBuilder
{
    /**
     * @var SearchResultFactory
     */
    private $searchResultFactory;

    /**
     * @var AggregationBuilder
     */
    private $aggregationBuilder;

    public function __construct(
        SearchResultFactory $searchResultFactory,
        AggregationBuilder $aggregationBuilder
    ) {
        $this->searchResultFactory = $searchResultFactory;
        $this->aggregationBuilder = $aggregationBuilder;
    }

    /**
     * Build search response
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @param SearchFullTextGet200Response|ListingResponse|Error|null $response
     * @return SearchResultInterface
     */
    public function build(SearchCriteriaInterface $searchCriteria, ?ModelInterface $response = null): SearchResultInterface
    {
        $searchResult = $this->searchResultFactory->create();

        $documents = [];
        $aggregations = null;
        $total = null;

        if ($response) {
            foreach ($response->getData() as $item) {
                if (isset($item['entity_id'])) {
                    $documents[] = new Document([DocumentInterface::ID => $item['entity_id']]);
                }
            }

            $total = $response->getMeta() ? $response->getMeta()->getTotalCount() : 0;
            $aggregations = $this->aggregationBuilder->build($searchCriteria, $response);
        }

        $searchResult->setItems($documents);
        $searchResult->setAggregations($aggregations);
        $searchResult->setTotalCount($total);

        return $searchResult;
    }
}