<?php

namespace Synerise\Integration\Search\Autocomplete\DataProvider;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Search\Model\Autocomplete\DataProviderInterface;
use Magento\Search\Model\Autocomplete\ItemFactory;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Search\Autocomplete\DataSource\DataSourceInterface;
use Synerise\Integration\Search\Autocomplete\DataBuilder\ProductDataBuilder;

class Product implements DataProviderInterface
{
    /**
     * @var DataSourceInterface
     */
    protected $dataSource;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var ProductDataBuilder
     */
    protected $productDataBuilder;

    /**
     * @var ItemFactory
     */
    protected $itemFactory;

    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(
        DataSourceInterface $dataSource,
        CollectionFactory $collectionFactory,
        ProductDataBuilder $productDataBuilder,
        ItemFactory $itemFactory,
        Logger $logger
    ) {
        $this->dataSource = $dataSource;
        $this->collectionFactory = $collectionFactory;
        $this->productDataBuilder = $productDataBuilder;
        $this->itemFactory = $itemFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function getItems()
    {
        $result = [];

        try {
            $dataSource = $this->dataSource->get();
            if (!$dataSource || empty($dataSource->getValues())) {
                return [];
            }

            $ids = $dataSource->getValues();

            $collection = $this->collectionFactory->create()
                ->addIdFilter(array_keys($ids))
                ->addAttributeToSelect(ProductDataBuilder::DEFAULT_ATTRIBUTES);

            if (!empty($dataSource->getHeader())) {
                $result[] = $this->itemFactory->create([
                    'type' => 'header',
                    'isSelectable' => false,
                    'title' => __($dataSource->getHeader())
                ]);
            }

            $items = [];
            foreach ($collection as $product) {
                $items[] = $product->getSku();
                $key = $ids[$product->getEntityId()]+1;

                if ($dataSource->getCampaignId()) {
                    $event = [
                        'action' => 'recommendation.click',
                        'data' => [
                            'campaignId' => $dataSource->getCampaignId(),
                            'correlationId' => $dataSource->getCorrelationId(),
                            'item' => $product->getSku()
                        ],
                        'label' => 'Recommended item was clicked'
                    ];
                } else {
                    $event = [
                        'action' => 'item.search.click',
                        'data' => [
                            'correlationId' => $dataSource->getCorrelationId(),
                            'item' => $product->getSku(),
                            'position' => $key,
                            'searchType' => "autocomplete"
                        ],
                        'label' => 'Search item was clicked'
                    ];
                }

                $result[$key] = $this->itemFactory->create(
                    $this->productDataBuilder->get($product, $event)
                );
            }

            if (!empty($dataSource->getCampaignId())) {
                $result[] = $this->itemFactory->create([
                    'type' => 'event',
                    'action' => 'recommendation.view',
                    'data' => [
                        'campaignId' => $dataSource->getCampaignId(),
                        'correlationId' => $dataSource->getCorrelationId(),
                        'items' => $items
                    ],
                    'label' => 'Recommended items were displayed'
                ]);
            }

        } catch (\Exception $e) {
            $this->logger->debug($e);
        }

        ksort($result);
        return $result;
    }
}