<?php
namespace Synerise\Integration\Search\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Config\ReaderInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Synerise\Integration\Model\SearchIndex;

class Reader implements ReaderInterface
{
    public const SEARCH_PRODUCTS_ENABLED = 'synerise/ai_search/products_enabled';

    public const SEARCH_PRODUCTS_COUNT = 'synerise/ai_search/products_count';

    public const SEARCH_SUGGESTIONS_ENABLED = 'synerise/ai_search/suggestions_enabled';

    public const SEARCH_SUGGESTIONS_INDEX = 'synerise/ai_search/suggestions_index';

    public const SEARCH_SUGGESTIONS_COUNT = 'synerise/ai_search/suggestions_count';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ObjectManagerInterface $objectManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->objectManager = $objectManager;
    }

    /**
     * Read configuration
     *
     * @param mixed $scope
     * @return array
     */
    public function read($scope = null): array
    {
        return [
            'productsAutocompleteEnabled' => $this->isProductsAutocompleteEnabled($scope),
            'productsAutocompleteLimit' => $this->getProductsAutocompleteLimit($scope),
            'searchIndex' => $this->getSearchIndex($scope),
            'suggestionsAutocompleteEnabled' => $this->isSuggestionsAutocompleteEnabled($scope),
            'suggestionsAutocompleteLimit' => $this->getSuggestionsAutocompleteLimit($scope),
            'suggestionsIndex' => $this->getSuggestionsIndex($scope)
        ];
    }

    /**
     * Is products autocomplete enabled
     *
     * @param $storeId
     * @return bool
     */
    protected function isProductsAutocompleteEnabled($storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::SEARCH_PRODUCTS_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get products autocomplete limit
     *
     * @param $storeId
     * @return int
     */
    protected function getProductsAutocompleteLimit($storeId): int
    {
        return (int) $this->scopeConfig->getValue(
            self::SEARCH_PRODUCTS_COUNT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get search index
     *
     * @param int $storeId
     * @return string|null
     */
    protected function getSearchIndex(int $storeId): ?string
    {
        /** @var SearchIndex $searchIndex */
        $searchIndex = $this->objectManager->create(SearchIndex::class)
            ->load($storeId, 'store_id');

        return $searchIndex ? $searchIndex->getIndexId() : null;
    }

    /**
     * Is suggestions autocomplete enabled
     *
     * @param $storeId
     * @return bool
     */
    protected function isSuggestionsAutocompleteEnabled($storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::SEARCH_SUGGESTIONS_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get suggestions autocomplete limit
     *
     * @param $storeId
     * @return int
     */
    protected function getSuggestionsAutocompleteLimit($storeId): int
    {
        return (int) $this->scopeConfig->getValue(
            self::SEARCH_SUGGESTIONS_COUNT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get suggestions index
     *
     * @param int $storeId
     * @return string|null
     */
    protected function getSuggestionsIndex(int $storeId): ?string
    {
        return $this->scopeConfig->getValue(
            self::SEARCH_SUGGESTIONS_INDEX,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
