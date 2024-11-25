<?php

namespace Synerise\Integration\Search\DataProvider;

use Magento\AdvancedSearch\Model\SuggestedQueriesInterface;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Search\Model\QueryInterface;
use Magento\Search\Model\QueryResultFactory;
use Magento\Store\Model\ScopeInterface;
use Synerise\ItemsSearchApiClient\Model\FullTextSuggestionSchema;

class Suggestions implements SuggestedQueriesInterface
{
    /**
     * @var Resolver
     */
    protected $layerResolver;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var QueryResultFactory
     */
    protected $queryResultFactory;

    /**
     * @var FullTextSuggestionSchema[]|null
     */
    protected $searchResponse;

    public function __construct(
        Resolver $layerResolver,
        ScopeConfigInterface $scopeConfig,
        QueryResultFactory $queryResultFactory
    ) {
        $this->layerResolver = $layerResolver;
        $this->scopeConfig = $scopeConfig;
        $this->queryResultFactory = $queryResultFactory;
    }

    /**
     * @inheritDoc
     */
    public function getItems(QueryInterface $query): array
    {
        $result = [];

        if ($this->isSuggestionsAllowed() && $response = $this->getResponseSuggestions()) {
            $maxItems = $this->getSearchSuggestionsCount();
            foreach ($response as $key => $suggestion) {
                if ($key > $maxItems) {
                    break;
                }

                $result[] = $this->queryResultFactory->create(
                    [
                        'queryText' => $suggestion['text'],
                        'resultsCount' => 0
                    ]
                );
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function isResultsCountEnabled(): bool
    {
        return false;
    }

    /**
     * Get search suggestions Max Count from config
     *
     * @return int
     */
    public function getSearchSuggestionsCount(): int
    {
        return (int) $this->scopeConfig->getValue(
            SuggestedQueriesInterface::SEARCH_SUGGESTION_COUNT,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Is Search Suggestions Allowed
     *
     * @return bool
     */
    public function isSuggestionsAllowed(): bool
    {
        return $this->scopeConfig->isSetFlag(
            SuggestedQueriesInterface::SEARCH_SUGGESTION_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return FullTextSuggestionSchema[]|null
     */
    public function getResponseSuggestions(): ?array
    {
        $layer = $this->layerResolver->get();
        return $layer ? $layer->getSuggestions() : [];
    }

}