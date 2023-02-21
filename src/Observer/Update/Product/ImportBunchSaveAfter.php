<?php

namespace Synerise\Integration\Observer\Update\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Synchronization\Sender\Product as ProductSender;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Observer\AbstractObserver;

class ImportBunchSaveAfter  extends AbstractObserver implements ObserverInterface
{
    const EVENT = 'catalog_product_import_bunch_save_after';

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var FilterGroup
     */
    private $filterGroup;

    /**
     * @var SearchCriteriaInterface
     */
    private $searchCriteria;

    /**
     * @var Synchronization
     */
    protected $synchronization;


    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        FilterBuilder $filterBuilder,
        FilterGroup $filterGroup,
        SearchCriteriaInterface $criteria,
        Synchronization $synchronization
    ) {
        $this->productRepository = $productRepository;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroup = $filterGroup;
        $this->searchCriteria = $criteria;

        $this->synchronization = $synchronization;

        parent::__construct($scopeConfig, $logger);
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        $bunch = $observer->getBunch();

        $bunchLimit = 500;
        $chunkBunches = array_chunk($bunch, $bunchLimit);

        foreach ($chunkBunches as $chunk) {
            $products = $this->getProductsBySkuInBunch($chunk);
            $this->synchronization->addItemsToQueuePerStore(
                $products,
                ProductSender::MODEL,
                ProductSender::ENTITY_ID
            );
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
