<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;

class ProductImportBunchSaveAfter implements ObserverInterface
{
    const EVENT = 'catalog_product_import_bunch_save_after';

    /**
     * @var \Synerise\Integration\Cron\Synchronization
     */
    protected $synchronization;

    /**
     * @var \Synerise\Integration\Helper\Tracking
     */
    protected $trackingHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

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

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Synerise\Integration\Cron\Synchronization $synchronization,
        \Synerise\Integration\Helper\Tracking $trackingHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\Search\FilterGroup $filterGroup,
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $this->logger = $logger;
        $this->synchronization = $synchronization;
        $this->trackingHelper = $trackingHelper;
        $this->productRepository = $productRepository;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroup = $filterGroup;
        $this->searchCriteria = $criteria;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $bunch = $observer->getBunch();

        $bunchLimit = 500;
        $chunkBunches = array_chunk($bunch, $bunchLimit);

        foreach ($chunkBunches as $chunk) {
            $products = $this->getProductsBySkuInBunch($chunk);
            $this->synchronization->addItemsToQueueByItemWebsiteIds('product', $products);
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
