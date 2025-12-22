<?php

namespace Synerise\Integration\Search;

use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Search\Api\SearchInterface;
use Synerise\Integration\Api\SearchIndexRepositoryInterface;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\Search\Container\SearchResponse;
use Synerise\Integration\Search\SearchRequest\SearchCriteriaBuilder;
use Synerise\Integration\SyneriseApi\Mapper\Search\FullText;
use Synerise\Integration\SyneriseApi\Sender\Search as Sender;
use Synerise\ItemsSearchApiClient\Model\SearchFullTextPostRequest;

class FullTextSearch extends AbstractSearch implements SearchInterface
{
    /**
     * @var Resolver
     */
    protected $layerResolver;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var SearchResponse
     */
    private $searchResponse;

    /**
     * @var Sender
     */
    protected $sender;

    /**
     * @var FullText
     */
    protected $mapper;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param Resolver $layerResolver
     * @param ScopeResolverInterface $scopeResolver
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SearchResponseBuilder $searchResponseBuilder
     * @param SearchResponse $searchResponse
     * @param Sender $sender
     * @param FullText $mapper
     * @param Cookie $cookieHelper
     * @param Logger $logger
     */
    public function __construct(
        SearchIndexRepositoryInterface $searchIndexRepository,
        Resolver $layerResolver,
        ScopeResolverInterface $scopeResolver,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SearchResponseBuilder $searchResponseBuilder,
        SearchResponse $searchResponse,
        Sender $sender,
        FullText $mapper,
        Cookie $cookieHelper,
        Logger $logger
    ) {
        $this->layerResolver = $layerResolver;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->searchResponse = $searchResponse;
        $this->sender = $sender;
        $this->mapper = $mapper;

        parent::__construct($searchIndexRepository, $scopeResolver, $searchResponseBuilder, $cookieHelper, $logger);
    }

    /**
     * @inheritDoc
     */
    public function search(SearchCriteriaInterface $searchCriteria): SearchResultInterface
    {
        try {
            $request = $this->mapper->prepareRequest(
                $this->searchCriteriaBuilder->build($searchCriteria, SearchCriteriaBuilder::TYPE_SEARCH),
                $this->getUuid()
            );

            $hash = $this->searchHash($request);
            $sessionCorrelationId = $this->searchResponse->getCorrelationId($hash);
            if ($sessionCorrelationId) {
                $request->setCorrelationId($sessionCorrelationId);
            }

            $searchFullTextPostResponse = $this->sender->searchFullText(
                $this->getStoreId(),
                $this->getSearchIndex($this->getStoreId()),
                $request
            );

            $currentCorrelationId = $searchFullTextPostResponse->getExtras()->getCorrelationId();
            if ($currentCorrelationId != $sessionCorrelationId) {
                $this->searchResponse->setCorrelationId($hash, $currentCorrelationId);
            }

            $searchResponse = $this->searchResponseBuilder->build($searchCriteria, $searchFullTextPostResponse)
                ->setSearchCriteria($searchCriteria);

            $layer = $this->layerResolver->get();
            $suggestions = $searchFullTextPostResponse->getExtras()->getSuggestions();
            if ($layer && $suggestions) {
                $layer->setSuggestions($suggestions);
            }

            return $searchResponse;

        } catch (\Exception $e) {
            $this->logger->debug($e);
            // return empty search result in case an exception is thrown
            return $this->searchResponseBuilder->build($searchCriteria)
                ->setSearchCriteria($searchCriteria);
        }
    }

    protected function searchHash(SearchFullTextPostRequest $request): string
    {
        return md5(json_encode([
            'query' => $request->getQuery(),
            'limit' => $request->getLimit(),
            'sort_by' => $request->getSortBy(),
            'ordering' => $request->getOrdering(),
            'filters' => $request->getFilters()
        ]));
    }
}