<?php

namespace Synerise\Integration\Search;

use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Search\Api\SearchInterface;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\SyneriseApi\Mapper\Search\Listing;
use Synerise\Integration\SyneriseApi\Sender\Search as Sender;

class ListingSearch extends AbstractSearch implements SearchInterface
{
    /**
     * @var Sender
     */
    protected $sender;

    /**
     * @var Listing
     */
    protected $mapper;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param ScopeResolverInterface $scopeResolver
     * @param SearchResponseBuilder $searchResponseBuilder
     * @param Sender $sender
     * @param Listing $mapper
     * @param Cookie $cookieHelper
     * @param Logger $logger
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ScopeResolverInterface $scopeResolver,
        SearchResponseBuilder $searchResponseBuilder,
        Sender $sender,
        Listing $mapper,
        Cookie $cookieHelper,
        Logger $logger
    ) {
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
            $listingPostResponse = $this->sender->listing(
                $this->getStoreId(),
                $this->getSearchIndex($this->getStoreId()),
                $this->mapper->prepareRequest($searchCriteria, $this->getUuid())
            );

            return $this->searchResponseBuilder->build($searchCriteria, $listingPostResponse)
                ->setSearchCriteria($searchCriteria);

        } catch (\Exception $e) {
            $this->logger->debug($e);
            // return empty search result in case an exception is thrown
            return $this->searchResponseBuilder->build($searchCriteria)
                ->setSearchCriteria($searchCriteria);
        }
    }
}