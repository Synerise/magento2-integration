<?php
namespace Synerise\Integration\Search\Recommendation\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Config\ReaderInterface;
use Magento\Store\Model\ScopeInterface;

class Reader implements ReaderInterface
{
    public const SEARCH_PRODUCTS_ENABLED = 'synerise_autocomplete/default/products_enabled';

    public const AUTOCOMPLETE_NO_RESULTS_ENABLED = 'synerise_autocomplete/no_results/enabled';

    public const AUTOCOMPLETE_NO_RESULTS_TOP_ENABLED = 'synerise_autocomplete/no_results/top_enabled';

    public const AUTOCOMPLETE_NO_RESULTS_TOP_HEADER = 'synerise_autocomplete/no_results/top_header';

    public const AUTOCOMPLETE_NO_RESULTS_HEADER = 'synerise_autocomplete/no_results/campaign_header';

    public const AUTOCOMPLETE_NO_RESULTS_CAMPAIGN_ID = 'synerise_autocomplete/no_results/campaign_id';

    public const AUTOCOMPLETE_ZERO_STATE_ENABLED = 'synerise_autocomplete/zero_state/enabled';

    public const AUTOCOMPLETE_ZERO_STATE_RECENT_ENABLED = 'synerise_autocomplete/zero_state/recent_enabled';

    public const AUTOCOMPLETE_ZERO_STATE_RECENT_HEADER = 'synerise_autocomplete/zero_state/recent_header';

    public const AUTOCOMPLETE_ZERO_STATE_HEADER = 'synerise_autocomplete/zero_state/campaign_header';

    public const AUTOCOMPLETE_ZERO_STATE_CAMPAIGN_ID = 'synerise_autocomplete/zero_state/campaign_id';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
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
            'noResults' => [
                'enabled' => $this->isProductsAutocompleteEnabled($scope) && $this->isNoResultsEnabled($scope),
                'header' => $this->getNoResultsHeader($scope),
                'campaignId' => $this->getNoResultsCampaignId($scope)
            ],
            'noResultsTop' => [
                'enabled' => $this->isNoResultsTopEnabled($scope),
                'header' => $this->getNoResultsTopHeader($scope)
            ],
            'zeroState' => [
                'enabled' => $this->isProductsAutocompleteEnabled($scope) && $this->isZeroStateEnabled($scope),
                'header' => $this->getZeroStateHeader($scope),
                'campaignId' => $this->getZeroStateCampaignId($scope)
            ],
            'zeroStateRecent' => [
                'enabled' => $this->isZeroStateRecentEnabled($scope),
                'header' => $this->getZeroStateRecentHeader($scope)
            ]
        ];
    }

    /**
     * Is Top searches for no results enabled
     *
     * @param $storeId
     * @return bool
     */
    protected function isNoResultsTopEnabled($storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::AUTOCOMPLETE_NO_RESULTS_TOP_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get header for no results Top searches
     *
     * @param $storeId
     * @return string|null
     */
    protected function getNoResultsTopHeader($storeId): ?string
    {
        return $this->scopeConfig->getValue(
            self::AUTOCOMPLETE_NO_RESULTS_TOP_HEADER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Is autocomplete for no results enabled
     *
     * @param $storeId
     * @return bool
     */
    protected function isNoResultsEnabled($storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::AUTOCOMPLETE_NO_RESULTS_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get header for no results campaign
     *
     * @param $storeId
     * @return string|null
     */
    protected function getNoResultsHeader($storeId): ?string
    {
        return $this->scopeConfig->getValue(
            self::AUTOCOMPLETE_NO_RESULTS_HEADER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get ID for no results campaign
     *
     * @param $storeId
     * @return string|null
     */
    protected function getNoResultsCampaignId($storeId): ?string
    {
        return $this->scopeConfig->getValue(
            self::AUTOCOMPLETE_NO_RESULTS_CAMPAIGN_ID,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Is autocomplete for zero-stae enabled
     *
     * @param $storeId
     * @return bool
     */
    protected function isZeroStateEnabled($storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::AUTOCOMPLETE_ZERO_STATE_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get header for zero-state campaign
     *
     * @param $storeId
     * @return string|null
     */
    protected function getZeroStateHeader($storeId): ?string
    {
        return $this->scopeConfig->getValue(
            self::AUTOCOMPLETE_ZERO_STATE_HEADER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Is Recent searches enabled for zero-state autocomplete
     *
     * @param $storeId
     * @return bool
     */
    protected function isZeroStateRecentEnabled($storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::AUTOCOMPLETE_ZERO_STATE_RECENT_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get header for zero-state Recent searches
     *
     * @param $storeId
     * @return string|null
     */
    protected function getZeroStateRecentHeader($storeId): ?string
    {
        return $this->scopeConfig->getValue(
            self::AUTOCOMPLETE_ZERO_STATE_RECENT_HEADER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get ID for zero-state campaign
     *
     * @param $storeId
     * @return string|null
     */
    protected function getZeroStateCampaignId($storeId): ?string
    {
        return $this->scopeConfig->getValue(
            self::AUTOCOMPLETE_ZERO_STATE_CAMPAIGN_ID,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
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
}
