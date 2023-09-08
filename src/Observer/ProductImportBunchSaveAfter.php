<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Synerise\Integration\Model\Synchronization\Product as SyncProduct;

class ProductImportBunchSaveAfter implements ObserverInterface
{
    const EVENT = 'catalog_product_import_bunch_save_after';

    /**
     * @var \Synerise\Integration\Helper\Tracking
     */
    protected $trackingHelper;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var \Magento\Framework\Api\Search\FilterGroup
     */
    private $filterGroup;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaInterface
     */
    private $searchCriteria;

    /**
     * @var SyncProduct
     */
    private $syncProduct;

    public function __construct(
        SyncProduct $syncProduct,
        \Synerise\Integration\Helper\Tracking $trackingHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\Search\FilterGroup $filterGroup,
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $this->syncProduct = $syncProduct;
        $this->trackingHelper = $trackingHelper;
        $this->productRepository = $productRepository;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroup = $filterGroup;
        $this->searchCriteria = $criteria;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->trackingHelper->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        $bunch = $observer->getBunch();

        $bunchLimit = 500;
        $chunkBunches = array_chunk($bunch, $bunchLimit);

        foreach ($chunkBunches as $chunk) {
            $products = $this->getProductsBySkuInBunch($chunk);
            $this->syncProduct->addItemsToQueue($products);
        }
    }

    /**
     * @param array $bunch
     * @return array
     */
    public function getProductsBySkuInBunch(array $bunch)
    {
        $this->filterGroup->setFilters([
            $this->filterBuilder
                ->setField('sku')
                ->setConditionType('in')
                ->setValue(array_unique(array_column($bunch, 'sku')))
                ->create()
        ]);

        $this->searchCriteria->setFilterGroups([$this->filterGroup]);
        $products = $this->productRepository->getList($this->searchCriteria);
        return $products->getItems();
    }
}
