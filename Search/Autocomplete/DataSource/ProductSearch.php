<?php

namespace Synerise\Integration\Search\Autocomplete\DataSource;

use Magento\Catalog\Model\Product\Visibility;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\Search\Attributes\Config as AttributesConfig;
use Synerise\Integration\Search\SearchRequest\DefaultFiltersBuilder;
use Synerise\Integration\Search\SearchRequest\Filters;
use Synerise\Integration\SyneriseApi\Mapper\Search\Autocomplete as Mapper;
use Synerise\Integration\SyneriseApi\Sender\Search as Sender;

class ProductSearch implements DataSourceInterface
{
    /**
     * @var DataFactory
     */
    protected $dataFactory;

    /**
     * @var QueryFactory
     */
    protected $queryFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var DefaultFiltersBuilder
     */
    protected $defaultFiltersBuilder;

    /**
     * @var AttributesConfig
     */
    protected $attributesConfig;

    /**
     * @var Cookie
     */
    protected $cookieHelper;

    /**
     * @var Sender
     */
    protected $sender;

    /**
     * @var Mapper
     */
    protected $mapper;

    /**
     * @var array
     */
    protected $sourceConfig;

    public function __construct(
        DataFactory $dataFactory,
        QueryFactory $queryFactory,
        StoreManagerInterface $storeManager,
        DefaultFiltersBuilder $defaultFiltersBuilder,
        AttributesConfig $attributesConfig,
        Cookie $cookieHelper,
        Sender $sender,
        Mapper $mapper,
        array $sourceConfig = []
    ) {
        $this->dataFactory = $dataFactory;
        $this->queryFactory = $queryFactory;
        $this->storeManager = $storeManager;
        $this->defaultFiltersBuilder = $defaultFiltersBuilder;
        $this->attributesConfig = $attributesConfig;
        $this->cookieHelper = $cookieHelper;
        $this->sender = $sender;
        $this->mapper = $mapper;
        $this->sourceConfig = $sourceConfig;
    }


    public function get(): ?DataInterface
    {
        $storeId = $this->storeManager->getStore()->getId();
        if (empty($this->sourceConfig['index_id'])) {
            throw new \InvalidArgumentException(sprintf('Search index not set for store: %d.', $storeId));
        }

        $request = $this->mapper->prepareRequest(
            $this->queryFactory->get()->getQueryText(),
            $this->sourceConfig['limit'],
            $this->cookieHelper->getSnrsUuid(),
            $this->getFilters()
        );

        $response = $this->sender->searchAutocomplete(
            $storeId,
            $this->sourceConfig['index_id'],
            $request
        );

        if ($response) {
            $ids = [];
            foreach ($response->getData() as $key => $item) {
                if (isset($item['entity_id'])) {
                    $ids[$item['entity_id']] = $key;
                }
            }

            $extras = $response->getExtras();
            $correlationId = $extras ? $extras->getCorrelationId() : null;
        }

        return $this->dataFactory->create([
            'values' => $ids,
            'correlation_id' => $correlationId
        ]);
    }

    protected function getFilters(): Filters
    {
        $filters = $this->defaultFiltersBuilder->build();

        $filters->setData(
            $this->getVisibilityFiledName(), ['in' => $this->getVisibleInSearchIds()]
        );

        return $filters;
    }

    /**
     * Get mapped field for visibility
     *
     * @return string
     */
    protected function getVisibilityFiledName(): string
    {
        return $this->attributesConfig->getMappedFieldName('visibility');
    }

    /**
     * Retrieve visible in search ids array
     *
     * @return int[]
     */
    protected function getVisibleInSearchIds(): array
    {
        return [Visibility::VISIBILITY_IN_SEARCH, Visibility::VISIBILITY_BOTH];
    }
}