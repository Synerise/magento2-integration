<?php

namespace Synerise\Integration\Search;

use InvalidArgumentException;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Search\Api\SearchInterface;
use Synerise\Integration\Api\SearchIndexRepositoryInterface;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking\Cookie;

abstract class AbstractSearch implements SearchInterface
{
    /**
     * @var SearchIndexRepositoryInterface
     */
    protected $searchIndexRepository;

    /**
     * @var ScopeResolverInterface
     */
    protected $scopeResolver;

    /**
     * @var SearchResponseBuilder
     */
    protected $searchResponseBuilder;

    /**
     * @var Cookie
     */
    protected $cookieHelper;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param SearchIndexRepositoryInterface $searchIndexRepository
     * @param ScopeResolverInterface $scopeResolver
     * @param SearchResponseBuilder $searchResponseBuilder
     * @param Cookie $cookieHelper
     * @param Logger $logger
     */
    public function __construct(
        SearchIndexRepositoryInterface $searchIndexRepository,
        ScopeResolverInterface $scopeResolver,
        SearchResponseBuilder $searchResponseBuilder,
        Cookie $cookieHelper,
        Logger $logger
    ) {
        $this->searchIndexRepository = $searchIndexRepository;
        $this->scopeResolver = $scopeResolver;
        $this->searchResponseBuilder = $searchResponseBuilder;
        $this->cookieHelper = $cookieHelper;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    abstract public function search(SearchCriteriaInterface $searchCriteria): SearchResultInterface;

    /**
     * Get client uuid
     *
     * @return string|null
     */
    protected function getUuid()
    {
        return $this->cookieHelper->getSnrsUuid();
    }

    /**
     * Get current Store ID
     *
     * @return int
     */
    protected function getStoreId()
    {
        return $this->scopeResolver->getScope()->getId();
    }

    /**
     * @param int|null $storeId
     * @return string|null
     * @throws NoSuchEntityException
     */
    protected function getSearchIndex(?int $storeId): ?string
    {
        $searchIndex =  $this->searchIndexRepository->getByStoreId($storeId);
        $indexId = $searchIndex->getIndexId();

        if (!$indexId) {
            throw new InvalidArgumentException(sprintf('Search index not set for store: %d.', $storeId));
        }

        return $indexId;
    }
}