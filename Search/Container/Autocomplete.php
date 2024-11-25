<?php

namespace Synerise\Integration\Search\Container;

use InvalidArgumentException;
use Magento\CatalogSearch\Model\Autocomplete\DataProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\ScopeInterface;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\Model\SearchIndex;
use Synerise\Integration\SyneriseApi\Mapper\Search\Autocomplete as Mapper;
use Synerise\Integration\SyneriseApi\Sender\Search as Sender;
use Synerise\ItemsSearchApiClient\Model\Error;
use Synerise\ItemsSearchApiClient\Model\SearchAutocompleteGet200Response;

class Autocomplete
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var QueryFactory
     */
    protected $queryFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ScopeResolverInterface
     */
    protected $scopeResolver;

    /**
     * @var Cookie
     */
    protected $cookieHelper;

    /**
     * @var Mapper
     */
    protected $mapper;

    /**
     * @var Sender
     */
    protected $sender;

    /**
     * @var Error|SearchAutocompleteGet200Response
     */
    protected $response;

    public function __construct(
        ObjectManagerInterface $objectManager,
        QueryFactory $queryFactory,
        ScopeConfigInterface $scopeConfig,
        ScopeResolverInterface $scopeResolver,
        Cookie $cookieHelper,
        Mapper $mapper,
        Sender $sender
    ) {
        $this->objectManager = $objectManager;
        $this->queryFactory = $queryFactory;
        $this->scopeConfig = $scopeConfig;
        $this->scopeResolver = $scopeResolver;
        $this->cookieHelper = $cookieHelper;
        $this->mapper = $mapper;
        $this->sender = $sender;
    }

    /**
     * Perform search or get response
     *
     * @return Error|SearchAutocompleteGet200Response
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \Synerise\ItemsSearchApiClient\ApiException
     */
    public function search()
    {
        if (!$this->response) {
            $storeId = $this->scopeResolver->getScope()->getId();
            if (!$indexId = $this->getSearchIndex($storeId)) {
                throw new InvalidArgumentException(sprintf('Search index not set for store: %d.', $storeId));
            }

            $this->response = $this->sender->searchAutocomplete(
                $storeId,
                $indexId,
                $this->mapper->prepareRequest(
                    $this->queryFactory->get()->getQueryText(),
                    $this->getLimit($storeId),
                    $this->cookieHelper->getSnrsUuid()
                )
            );
        }

        return $this->response;
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

    /**
     * Get autocomplete limit
     *
     * @param int|null $storeId
     * @return int|null
     */
    protected function getLimit(?int $storeId = null): ?int
    {
        return (int) $this->scopeConfig->getValue(
            DataProvider::CONFIG_AUTOCOMPLETE_LIMIT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}