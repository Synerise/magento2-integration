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
        $config = $this->config;
        if (!isset($config[$group])) {
            throw new \InvalidArgumentException("Autocomplete group '{$group}' is not configured");
        }

        $dataProviders = [];
        if ($group != 'default' && empty($config[$group]['enabled'])) {
            return $dataProviders;
        }

        if ($config['default']['suggestions_enabled']) {
            // default
            if (!empty($config[$group]['suggestions_index'])) {
                $sourceConfig = [
                    'limit' => $config['default']['suggestions_count'],
                    'index_id' => $config['default']['suggestions_index']
                ];
                $dataSource = $this->dataSourceFactory->create(QuerySuggestion::class, $sourceConfig);
                $dataProviders[] = $this->createQueryDataProvider($dataSource);
            }

            // zero_state
            if (!empty($config[$group]['recent_enabled'])) {
                $sourceConfig = [
                    'header' => !empty($config[$group]['recent_header']) ? $config[$group]['recent_header'] : null,
                    'limit' => $config['default']['suggestions_count'],
                    'index_id' => $this->searchIndexRepository->getByStoreId($this->storeManager->getStore()->getId())->getIndexId()
                ];
                $dataSource = $this->dataSourceFactory->create(QueryRecent::class, $sourceConfig);
                $dataProviders[] = $this->createQueryDataProvider($dataSource);
            }

            // no_results
            if (!empty($config[$group]['top_enabled'])) {
                $sourceConfig = [
                    'header' => !empty($config[$group]['top_header']) ? $config[$group]['top_header'] : null,
                    'limit' => $config['default']['suggestions_count'],
                    'index_id' => $config['default']['suggestions_index']
                ];
                $dataSource = $this->dataSourceFactory->create(QueryTop::class, $sourceConfig);
                $dataProviders[] = $this->createQueryDataProvider($dataSource);
            }
        }

        if ($config['default']['products_enabled']) {
            // zero_state
            // no_results
            if (!empty($config[$group]['campaign_id'])) {
                $sourceConfig = [
                    'campaign_id' => $config[$group]['campaign_id'],
                    'header' => !empty($config[$group]['campaign_header']) ? $config[$group]['campaign_header'] : null
                ];
                $dataSource = $this->dataSourceFactory->create(ProductRecommendations::class, $sourceConfig);
                $dataProviders[] = $this->createProductDataProvider($dataSource);
            }

            if ($group === 'default') {
                $sourceConfig = [
                    'index_id' => $this->searchIndexRepository->getByStoreId($this->storeManager->getStore()->getId())->getIndexId(),
                    'limit' => $config['default']['products_count']
                ];
                $dataSource = $this->dataSourceFactory->create(ProductSearch::class, $sourceConfig);
                $dataProviders[] = $this->createProductDataProvider($dataSource);
            }
        }

        return $dataProviders;
    }

    private function initConfig()
    {
        $this->config = (array) $this->scopeConfig->getValue(
            self::XML_PATH,
            ScopeInterface::SCOPE_STORE
        );
    }

    private function createProductDataProvider(DataSourceInterface $dataSource): DataProviderInterface
    {
        return $this->dataProviderFactory->create(
            Product::class,
            $dataSource
        );
    }

    private function createQueryDataProvider(DataSourceInterface $dataSource): DataProviderInterface
    {
        return $this->dataProviderFactory->create(
            Query::class,
            $dataSource
        );
    }
}