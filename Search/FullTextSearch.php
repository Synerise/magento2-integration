<?php

namespace Synerise\Integration\Search;

use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Search\Api\SearchInterface;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\SyneriseApi\Mapper\Search\FullText;
use Synerise\Integration\SyneriseApi\Sender\Search as Sender;

class FullTextSearch extends AbstractSearch implements SearchInterface
{
    /**
     * @var Resolver
     */
    protected $layerResolver;

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
     * @param SearchResponseBuilder $searchResponseBuilder
     * @param Sender $sender
     * @param FullText $mapper
     * @param Cookie $cookieHelper
     * @param Logger $logger
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Resolver $layerResolver,
        ScopeResolverInterface $scopeResolver,
        SearchResponseBuilder $searchResponseBuilder,
        Sender $sender,
        FullText $mapper,
        Cookie $cookieHelper,
        Logger $logger
    ) {
        $this->layerResolver = $layerResolver;
        $this->sender = $sender;
        $this->mapper = $mapper;

        parent::__construct($objectManager, $scopeResolver, $searchResponseBuilder, $cookieHelper, $logger);
    }

    /**
     * @inheritDoc
     */
    public function search(SearchCriteriaInterface $searchCriteria): SearchResultInterface
    {
        try {
            $searchFullTextPostResponse = $this->sender->searchFullText(
                $this->getStoreId(),
                $this->getSearchIndex($this->getStoreId()),
                $this->mapper->prepareRequest($searchCriteria, $this->getUuid())
            );

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
}