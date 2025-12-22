<?php

namespace Synerise\Integration\Search\Autocomplete\DataSource;

use Magento\Store\Model\StoreManagerInterface;
use Synerise\Api\Search\Search\V2\Indices\Item\RecentSearches\RecentSearchesRequestBuilderGetQueryParameters;
use Synerise\Api\Search\Search\V2\Indices\Item\RecentSearches\RecentSearchesRequestBuilderGetRequestConfiguration;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\SyneriseApi\ConfigFactory as ApiConfigFactory;
use Synerise\Sdk\Api\ClientBuilderFactoryInterface;

class QueryRecent implements DataSourceInterface
{
    /**
     * @var DataFactory
     */
    private $dataFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Cookie
     */
    private $cookieHelper;

    /**
     * @var ApiConfigFactory
     */
    private $apiConfigFactory;

    /**
     * @var ClientBuilderFactoryInterface
     */
    private $clientBuilderFactory;

    /**
     * @var array
     */
    private $sourceConfig;

    public function __construct(
        DataFactory $dataFactory,
        StoreManagerInterface $storeManager,
        Cookie $cookieHelper,
        ApiConfigFactory $apiConfigFactory,
        ClientBuilderFactoryInterface $clientBuilderFactory,
        array $sourceConfig
    ) {
        $this->dataFactory = $dataFactory;
        $this->storeManager = $storeManager;
        $this->cookieHelper = $cookieHelper;
        $this->apiConfigFactory = $apiConfigFactory;
        $this->clientBuilderFactory = $clientBuilderFactory;
        $this->sourceConfig = $sourceConfig;
    }

    public function get(): ?DataInterface
    {
        $storeId = $this->storeManager->getStore()->getId();

        $indexId = $this->sourceConfig['index_id'];
        if (!$indexId) {
            throw new \InvalidArgumentException(sprintf('Suggestions index not set for store: %d.', $storeId));
        }

        $values = [];

        $client = $this->clientBuilderFactory->create($this->apiConfigFactory->create($storeId));
        $parameters = new RecentSearchesRequestBuilderGetQueryParameters($this->cookieHelper->getSnrsUuid());
        $requestConfiguration = new RecentSearchesRequestBuilderGetRequestConfiguration(null, null, $parameters);

        $response = $client->search()->search()->v2()->indices()->byIndexId($indexId)->recentSearches()->get($requestConfiguration)->wait();
        if ($response) {
            $values = $response;
        }

        return $this->dataFactory->create([
            'header' => $this->sourceConfig['header'],
            'values' => array_slice($values, 0, $this->sourceConfig['limit'], true),
        ]);
    }
}