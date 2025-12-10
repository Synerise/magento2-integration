<?php

namespace Synerise\Integration\Search\DataProvider\Autocomplete;

use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Search\Model\Autocomplete\DataProviderInterface;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\Search\Attributes\Config as AttributesConfig;
use Synerise\Integration\Search\DataProvider\Autocomplete\Product\ItemFactory;
use Synerise\Integration\Search\ConfigFactory;
use Synerise\Integration\Search\SearchRequest\DefaultFiltersBuilder;
use Synerise\Integration\Search\SearchRequest\Filters;
use Synerise\Integration\SyneriseApi\Mapper\Search\Autocomplete as Mapper;
use Synerise\Integration\SyneriseApi\Sender\Search as Sender;

class Product implements DataProviderInterface
{
    protected $defaultSelectedAttributes = [
        'name',
        'thumbnail',
        'price',
        'special_price',
        'special_from_date',
        'special_to_date',
        'price_type',
        'tax_class_id'
    ];

    /**
     * @var QueryFactory
     */
    protected $queryFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var DefaultFiltersBuilder
     */
    protected $defaultFiltersBuilder;

    /**
     * @var AttributesConfig
     */
    protected $attributesConfig;

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var ItemFactory
     */
    protected $itemFactory;

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
     * @var Logger
     */
    protected $logger;

    public function __construct(
        QueryFactory $queryFactory,
        StoreManagerInterface $storeManager,
        CollectionFactory $collectionFactory,
        DefaultFiltersBuilder $defaultFiltersBuilder,
        AttributesConfig $attributesConfig,
        ConfigFactory $configFactory,
        ItemFactory $itemFactory,
        Cookie $cookieHelper,
        Sender $sender,
        Mapper $mapper,
        Logger $logger
    ) {
        $this->queryFactory = $queryFactory;
        $this->storeManager = $storeManager;
        $this->collectionFactory = $collectionFactory;
        $this->defaultFiltersBuilder = $defaultFiltersBuilder;
        $this->attributesConfig = $attributesConfig;
        $this->configFactory = $configFactory;
        $this->itemFactory = $itemFactory;
        $this->cookieHelper = $cookieHelper;
        $this->sender = $sender;
        $this->mapper = $mapper;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function getItems()
    {
        $result = [];

        try {
            $storeId = $this->storeManager->getStore()->getId();
            $config = $this->configFactory->create($storeId);

            if (!$config->isProductsAutocompleteEnabled()) {
                return $result;
            }

            $indexId = $config->getSearchIndex();
            if (!$indexId) {
                throw new \InvalidArgumentException(sprintf('Search index not set for store: %d.', $storeId));
            }

            $request = $this->mapper->prepareRequest(
                $this->queryFactory->get()->getQueryText(),
                $config->getProductsAutocompleteLimit(),
                $this->cookieHelper->getSnrsUuid(),
                $this->getFilters()
            );

            $response = $this->sender->searchAutocomplete(
                $storeId,
                $indexId,
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

                $collection = $this->collectionFactory->create()
                    ->addIdFilter(array_keys($ids))
                    ->addAttributeToSelect(
                        $this->defaultSelectedAttributes
                    );

                foreach ($collection as $product) {
                    $key = $ids[$product->getEntityId()];
                    $result[$key] = $this->itemFactory->create($product, $key+1, $correlationId);
                }
            }
        } catch (\Exception $e) {
            $this->logger->debug($e);
        }

        ksort($result);
        return $result;
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