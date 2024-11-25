<?php

namespace Synerise\Integration\Search\SearchResponse;

use Magento\Elasticsearch\SearchAdapter\AggregationFactory;
use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Search\Request\Config as RequestConfig;
use Magento\Framework\Search\Request\NonExistingRequestNameException;
use Synerise\Integration\Search\Attributes\Config as AttributesConfig;
use Synerise\Integration\Search\SearchResponse\Aggregation\BucketBuilderFactoryInterface;
use Synerise\Integration\Search\SearchResponse\Aggregation\BucketBuilderFactoryProviderInterface;
use Synerise\ItemsSearchApiClient\Model\Error;
use Synerise\ItemsSearchApiClient\Model\ListingResponse;
use Synerise\ItemsSearchApiClient\Model\ModelInterface;
use Synerise\ItemsSearchApiClient\Model\SearchFullTextGet200Response;

class AggregationBuilder
{
    /**
     * @var AttributesConfig
     */
    private $attributesConfig;

    /**
     * @var RequestConfig
     */
    private $requestConfig;

    /**
     * @var AggregationFactory
     */
    private $aggregationFactory;

    /**
     * @var BucketBuilderFactoryInterface
     */
    private $bucketBuilderFactory;

    /**
     * @param AttributesConfig $attributesConfig
     * @param RequestConfig $requestConfig
     * @param AggregationFactory $aggregationFactory
     * @param BucketBuilderFactoryProviderInterface $bucketBuilderFactoryProvider
     */
    public function __construct(
        AttributesConfig $attributesConfig,
        RequestConfig $requestConfig,
        AggregationFactory $aggregationFactory,
        BucketBuilderFactoryProviderInterface $bucketBuilderFactoryProvider
    ) {
        $this->attributesConfig = $attributesConfig;
        $this->requestConfig = $requestConfig;
        $this->aggregationFactory = $aggregationFactory;
        $this->bucketBuilderFactory = $bucketBuilderFactoryProvider->get();
    }

    /**
     * Build aggregation
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @param SearchFullTextGet200Response|ListingResponse|Error|null $response
     * @return AggregationInterface|null
     */
    public function build(SearchCriteriaInterface $searchCriteria, ?ModelInterface $response = null): ?AggregationInterface
    {
        $rawAggregation = [];
        if ($facets = $this->getFacetsFromResponse($response)) {
            foreach($this->getConfiguredAggregations($searchCriteria->getRequestName()) as $bucket) {
                $rawAggregation[$bucket['name']] = [];
                $field = $this->attributesConfig->getMappedFieldName($bucket['field']) ?? $bucket['field'];
                if (isset($facets[$field])) {
                    $rawAggregation[$bucket['name']] = $this->bucketBuilderFactory->get($field)->build($facets[$field]);
                }
            }
        }
        return !empty($rawAggregation) ? $this->aggregationFactory->create($rawAggregation) : null;
    }

    /**
     * Get facets array from response
     *
     * @param SearchFullTextGet200Response|ListingResponse|Error|null $response
     * @return array
     */
    protected function getFacetsFromResponse(?ModelInterface $response = null): array
    {
        $facets = [];
        if ($response && $response->getExtras()) {
            $facets = $response->getExtras()->getFilteredFacets() ?? [];
        }
       return $facets;
    }

    /**
     * Get aggregations from configuration
     *
     * @param string $requestName
     * @return array|mixed
     */
    protected function getConfiguredAggregations(string $requestName)
    {
        $config = $this->getRequestConfig($requestName);
        return $config['aggregations'] ?? [];
    }

    /**
     * Get request config
     *
     * @param string $requestName
     * @return array|mixed
     */
    private function getRequestConfig(string $requestName)
    {
        $data = $this->requestConfig->get($requestName);
        if ($data === null) {
            throw new NonExistingRequestNameException(new Phrase("Request name '%1' doesn't exist.", [$requestName]));
        }
        return $data;
    }
}