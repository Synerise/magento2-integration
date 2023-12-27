<?php
namespace Synerise\Integration\Ui\DataProvider\Synchronization;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory as SubscriberCollectionFactory;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Model\Config\Source\Synchronization\Model;

class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var Synchronization
     */
    protected $synchronization;

    /**
     * @var CustomerCollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var SubscriberCollectionFactory
     */
    protected $subscriberCollectionFactory;

    public function __construct(
        ResourceConnection $resource,
        RequestInterface $request,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        Synchronization $synchronization,
        CustomerCollectionFactory $customerCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        SubscriberCollectionFactory $subscriberCollectionFactory,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
        $this->connection = $resource->getConnection();
        $this->synchronization = $synchronization;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->subscriberCollectionFactory = $subscriberCollectionFactory;

        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        $items = [];
        foreach(Model::OPTIONS as $modelKey => $modelName) {
            $items[] = [
                'name' => $modelName,
                'sent' => $this->getSentCount($modelKey, $this->request->getParam('store')),
                'total' => $this->getTotalCount($modelKey, $this->request->getParam('store')),
                'enabled' => $this->synchronization->isEnabledModel($modelKey) ? 'Yes' : 'No'
            ];
        }

        return [
            'items' => $items,
            'totalRecords' => count($items)
        ];
    }

    /**
     * @param string $model
     * @param int|null $storeId
     * @return int
     */
    protected function getSentCount(string $model, ?int $storeId = null): int
    {
        $select = $this->connection->select()->from(
            $this->connection->getTableName('synerise_sync_'.$model),
            "COUNT(DISTINCT {$model}_id)"
        );

        if ($storeId) {
            $select->where('store_id = ?', $storeId);
        }
        
        return (int) $this->connection->fetchOne($select);
    }
    /**
     * @param string $model
     * @param int|null $storeId
     * @return int
     */
    protected function getTotalCount(string $model, ?int $storeId = null): int
    {
        switch($model) {
            case 'customer':
                $collection = $this->customerCollectionFactory->create();
                if ($storeId) {
                    $collection->addFieldToFilter('store_id', ['in' => $storeId]);
                }
                break;
            case 'order':
                $collection = $this->orderCollectionFactory->create();
                if ($storeId) {
                    $collection->addFieldToFilter('store_id', ['in' => $storeId]);
                }
                break;
            case 'product':
                $collection = $this->productCollectionFactory->create();
                if ($storeId) {
                    $collection->addStoreFilter($storeId);
                }
                break;
            case 'subscriber':
                $collection = $this->subscriberCollectionFactory->create();
                if ($storeId) {
                    $collection->addStoreFilter($storeId);
                }
                break;
            default:
                $collection = null;
        }

        return $collection ? $collection->getSize() : 0;
    }
}
