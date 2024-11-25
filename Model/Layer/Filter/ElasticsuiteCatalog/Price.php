<?php

namespace Synerise\Integration\Model\Layer\Filter\ElasticsuiteCatalog;

use Smile\ElasticsuiteCatalog\Model\Search\Request\Field\Mapper as RequestFieldMapper;

class Price extends \Smile\ElasticsuiteCatalog\Model\Layer\Filter\Price
{
    /**
     * @var \Magento\Catalog\Model\Layer\Filter\DataProvider\Price
     */
    private $dataProvider;

    /**
     * Constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory               $filterItemFactory   Item filter factory.
     * @param \Magento\Store\Model\StoreManagerInterface                    $storeManager        Store manager.
     * @param \Magento\Catalog\Model\Layer                                  $layer               Search layer.
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder          $itemDataBuilder     Item data builder.
     * @param \Magento\Catalog\Model\ResourceModel\Layer\Filter\Price       $resource            Price resource.
     * @param \Magento\Customer\Model\Session                               $customerSession     Customer session.
     * @param \Magento\Framework\Search\Dynamic\Algorithm                   $priceAlgorithm      Price algorithm.
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface             $priceCurrency       Price currency.
     * @param \Magento\Catalog\Model\Layer\Filter\Dynamic\AlgorithmFactory  $algorithmFactory    Algorithm factory.
     * @param \Magento\Catalog\Model\Layer\Filter\DataProvider\PriceFactory $dataProviderFactory Data provider.
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory     $queryFactory        Query Factory.
     * @param RequestFieldMapper                                            $requestFieldMapper  Search request field mapper.
     * @param array                                                         $data                Custom data.
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Catalog\Model\ResourceModel\Layer\Filter\Price $resource,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Search\Dynamic\Algorithm $priceAlgorithm,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Catalog\Model\Layer\Filter\Dynamic\AlgorithmFactory $algorithmFactory,
        \Magento\Catalog\Model\Layer\Filter\DataProvider\PriceFactory $dataProviderFactory,
        \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory,
        RequestFieldMapper $requestFieldMapper,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $resource,
            $customerSession,
            $priceAlgorithm,
            $priceCurrency,
            $algorithmFactory,
            $dataProviderFactory,
            $queryFactory,
            $requestFieldMapper,
            $data
        );

        $this->dataProvider = $dataProviderFactory->create(['layer' => $this->getLayer()]);
    }

    /**
     * Apply price range filter
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return $this
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function apply(\Magento\Framework\App\RequestInterface $request)
    {
        /**
         * Filter must be string: $fromPrice-$toPrice
         */
        $filter = $request->getParam($this->getRequestVar());
        if (!$filter || is_array($filter)) {
            return $this;
        }

        $filterParams = explode(',', $filter);
        $filter = $this->dataProvider->validateFilter($filterParams[0]);
        if (!$filter) {
            return $this;
        }

        $this->dataProvider->setInterval($filter);
        $priorFilters = $this->dataProvider->getPriorFilters($filterParams);
        if ($priorFilters) {
            $this->dataProvider->setPriorIntervals($priorFilters);
        }

        list($from, $to) = $filter;

        $this->getLayer()->getProductCollection()->addFieldToFilter(
            'price',
            ['from' => $from, 'to' =>  empty($to) || $from == $to ? $to : $to - self::PRICE_DELTA]
        );

        $this->getLayer()->getState()->addFilter(
            $this->_createItem($this->_renderRangeLabel(empty($from) ? 0 : $from, $to), $filter)
        );

        return $this;
    }


    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * Get data array for building attribute filter items
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getItemsData()
    {
        $attribute = $this->getAttributeModel();
        $this->_requestVar = $attribute->getAttributeCode();

        /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $productCollection */
        $productCollection = $this->getLayer()->getProductCollection();
        $facets = $productCollection->getFacetedData('price');

        $minValue = false;
        $maxValue = false;

        $data = [];
        if (count($facets) > 1) {
            foreach ($facets as $key => $aggregation) {
                $count = $aggregation['count'];
                $data[] = ['label' => $key, 'value' => $key, 'count' => $count];

                if ($minValue === false || $minValue > $key) {
                    $minValue = $key;
                }

                if ($maxValue === false || $maxValue < $key) {
                    $maxValue = $key;
                }
            }

            $this->setMinValue($minValue);
            $this->setMaxValue($maxValue);
        }

        return $data;
    }

}