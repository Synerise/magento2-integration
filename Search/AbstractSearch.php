<?php

namespace Synerise\Integration\Search;

use InvalidArgumentException;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Search\Api\SearchInterface;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\Model\SearchIndex;

abstract class AbstractSearch implements SearchInterface
{
    public const XML_PATH_SYNERISE_SEARCH_INDEX = 'catalog/search/synerise_ai_index';

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

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
     * @param ObjectManagerInterface $objectManager
     * @param ScopeResolverInterface $scopeResolver
     * @param SearchResponseBuilder $searchResponseBuilder
     * @param Cookie $cookieHelper
     * @param Logger $logger
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ScopeResolverInterface $scopeResolver,
        SearchResponseBuilder $searchResponseBuilder,
        Cookie $cookieHelper,
        Logger $logger
    ) {
        $this->objectManager = $objectManager;
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
     */
    protected function getSearchIndex(?int $storeId): ?string
    {
        /** @var SearchIndex $searchIndex */
        $searchIndex = $this->objectManager->create(SearchIndex::class)
            ->load($storeId, 'store_id');

        $indexId = $searchIndex->getIndexId();

        if (!$indexId) {
            throw new InvalidArgumentException(sprintf('Search index not set for store: %d.', $storeId));
        }

        return $indexId;
    }
}