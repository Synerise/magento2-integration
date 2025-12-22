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
                    'title' => __($dataSource->getHeader())
                ]);
            }

            foreach ($collection as $product) {
                $key = $ids[$product->getEntityId()]+1;
                $result[$key] = $this->itemFactory->create(
                    $this->productDataBuilder->get($product, $key+1, $dataSource->getCorrelationId())
                );
            }
        } catch (\Exception $e) {
            $this->logger->debug($e);
        }

        ksort($result);
        return $result;
    }
}