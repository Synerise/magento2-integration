<?php

namespace Synerise\Integration\Model\Product;

use Magento\Catalog\Model\Config as CatalogConfig;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class CollectionBuilder
{
    /**
     * @var CatalogConfig
     */
    private CatalogConfig $catalogConfig;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $productCollectionFactory;

    public function __construct(
        CatalogConfig $catalogConfig,
        CollectionFactory $productCollectionFactory
    ) {
        $this->catalogConfig = $catalogConfig;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    public function build(
        array $ids,
        ?string $storeId = null,
        ?int $pageSize = 8,
        ?int $curPage = 1
    ): Collection {
        $collection = $this->productCollectionFactory->create();
        if ($storeId !== null) {
            $collection->setStoreId($storeId);
        }

        $collection = $this->addProductAttributesAndPrices($collection)
            ->addIdFilter($ids)
            ->addStoreFilter()
            ->setPageSize($pageSize)
            ->setCurPage($curPage);

        $collection->getSelect()->order(
            new \Zend_Db_Expr(
                'FIELD(e.entity_id, ' . implode(',', $ids) . ')'
            )
        );

        $collection->distinct(true);

        return $collection;
    }

    /**
     * Add all attributes and apply pricing logic to products collection
     * to get correct values in different products lists.
     * E.g. crosssells, upsells, new products, recently viewed
     *
     * @param Collection $collection
     * @return Collection
     */
    protected function addProductAttributesAndPrices(Collection $collection) {
        return $collection
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addAttributeToSelect($this->catalogConfig->getProductAttributes())
            ->addUrlRewrite();
    }
}