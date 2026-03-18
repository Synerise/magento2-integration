<?php

namespace Synerise\Integration\Search\Autocomplete;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Search\Model\Autocomplete\DataProviderInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\Integration\Api\SearchIndexRepositoryInterface;
use Synerise\Integration\Search\Autocomplete\DataProvider\Product;
use Synerise\Integration\Search\Autocomplete\DataProvider\Query;
use Synerise\Integration\Search\Autocomplete\DataSource\DataSourceInterface;
use Synerise\Integration\Search\Autocomplete\DataSource\ProductRecommendations;
use Synerise\Integration\Search\Autocomplete\DataSource\ProductSearch;
use Synerise\Integration\Search\Autocomplete\DataSource\QueryRecent;
use Synerise\Integration\Search\Autocomplete\DataSource\QuerySuggestion;
use Synerise\Integration\Search\Autocomplete\DataSource\QueryTop;

class DataProviderResolver
{
    private const XML_PATH = 'synerise_autocomplete';

    private $config = [];

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var DataProviderFactory
     */
    private $dataProviderFactory;

    /**
     * @var DataSourceFactory
     */
    private $dataSourceFactory;

    /**
     * @var SearchIndexRepositoryInterface
     */
    private $searchIndexRepository;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        DataProviderFactory $dataProviderFactory,
        DataSourceFactory $dataSourceFactory,
        SearchIndexRepositoryInterface $searchIndexRepository
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->dataProviderFactory = $dataProviderFactory;
        $this->dataSourceFactory = $dataSourceFactory;
        $this->searchIndexRepository = $searchIndexRepository;

        $this->initConfig();
    }

    /**
     * @param string $group
     * @return DataProviderInterface[]
     * @throws NoSuchEntityException|\InvalidArgumentException
     */
    public function resolve(string $group): array
    {
        if ($group == 'default') {
            return $this->resolveDefault();
        }

        return $this->resolveGroup($group);
    }

    /**
     * @param string $group
     * @return array
     * @throws \InvalidArgumentException
     */
    private function resolveGroup(string $group): array
    {
        $dataProviders = [];

        $defaultConfig = $this->config['default'];
        if (!$defaultConfig) {
            return $dataProviders;
        }

        $groupConfig = $this->config[$group];
        if (!$groupConfig) {
            throw new \InvalidArgumentException("Autocomplete group '{$group}' is not configured");
        }

        if (empty($groupConfig['enabled'])) {
            return $dataProviders;
        }

        $searchIndexId = $this->getSearchIndexId();
        if (!empty($groupConfig['recent_enabled']) && $searchIndexId) {
            $sourceConfig = [
                'header' => !empty($groupConfig['recent_header']) ? $groupConfig['recent_header'] : null,
                'limit' => $defaultConfig['suggestions_count'],
                'index_id' => $searchIndexId
            ];
            $dataSource = $this->dataSourceFactory->create(QueryRecent::class, $sourceConfig);
            $dataProviders[] = $this->createQueryDataProvider($dataSource);
        }

        if (!empty($groupConfig['top_enabled']) && !empty($defaultConfig['suggestions_index'])) {
            $sourceConfig = [
                'header' => !empty($groupConfig['top_header']) ? $groupConfig['top_header'] : null,
                'limit' => $defaultConfig['suggestions_count'],
                'index_id' => $defaultConfig['suggestions_index']
            ];
            $dataSource = $this->dataSourceFactory->create(QueryTop::class, $sourceConfig);
            $dataProviders[] = $this->createQueryDataProvider($dataSource);
        }

        if (!empty($groupConfig['campaign_id'])) {
            $sourceConfig = [
                'campaign_id' => $groupConfig['campaign_id'],
                'header' => !empty($groupConfig['campaign_header']) ? $groupConfig['campaign_header'] : null
            ];
            $dataSource = $this->dataSourceFactory->create(ProductRecommendations::class, $sourceConfig);
            $dataProviders[] = $this->createProductDataProvider($dataSource);
        }

        return $dataProviders;
    }

    /**
     * @return array
     */
    private function resolveDefault(): array
    {
        $dataProviders = [];

        $defaultConfig = $this->config['default'];
        if(!$defaultConfig) {
            return $dataProviders;
        }

        if ($defaultConfig['suggestions_enabled'] && !empty($defaultConfig['suggestions_index'])) {
            $sourceConfig = [
                'header' => !empty($defaultConfig['suggestions_header']) ? $defaultConfig['suggestions_header'] : null,
                'limit' => $defaultConfig['suggestions_count'],
                'index_id' => $defaultConfig['suggestions_index']
            ];
            $dataSource = $this->dataSourceFactory->create(QuerySuggestion::class, $sourceConfig);
            $dataProviders[] = $this->createQueryDataProvider($dataSource);
        }

        $searchIndexId = $this->getSearchIndexId();
        if ($defaultConfig['products_enabled'] && $searchIndexId) {
            $sourceConfig = [
                'header' => !empty($defaultConfig['products_header']) ? $defaultConfig['products_header'] : null,
                'index_id' => $searchIndexId,
                'limit' => $defaultConfig['products_count']
            ];
            $dataSource = $this->dataSourceFactory->create(ProductSearch::class, $sourceConfig);
            $dataProviders[] = $this->createProductDataProvider($dataSource);
        }

        return $dataProviders;
    }

    private function initConfig(): void
    {
        $this->config = (array) $this->scopeConfig->getValue(
            self::XML_PATH,
            ScopeInterface::SCOPE_STORE
        );
    }

    private function createProductDataProvider(DataSourceInterface $dataSource): DataProviderInterface
    {
        return $this->dataProviderFactory->create(Product::class, $dataSource);
    }

    private function createQueryDataProvider(DataSourceInterface $dataSource): DataProviderInterface
    {
        return $this->dataProviderFactory->create(Query::class, $dataSource);
    }

    private function getSearchIndexId(): ?string
    {
        try {
            $storeId = $this->storeManager->getStore()->getId();
            $searchIndex = $this->searchIndexRepository->getByStoreId($storeId);
            return $searchIndex->getIndexId();
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }
}