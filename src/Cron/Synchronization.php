<?php

namespace Synerise\Integration\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Model\AbstractSynchronization;
use Synerise\Integration\Model\ResourceModel\Cron\Status\CollectionFactory as StatusCollectionFactory;
use Synerise\Integration\Model\Synchronization\Customer;
use Synerise\Integration\Model\Synchronization\Order;
use Synerise\Integration\Model\Synchronization\Product;
use Synerise\Integration\Model\Synchronization\Subscriber;
use Synerise\Integration\Model\ResourceModel\Cron\Status as StatusResourceModel;

class Synchronization
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var StatusCollectionFactory
     */
    protected $statusCollectionFactory;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * @var array
     */
    protected $executors;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ResourceConnection $resource,
        LoggerInterface $logger,
        StatusCollectionFactory $statusCollectionFactory,
        Customer $customer,
        Order $order,
        Product $product,
        Subscriber $subscriber
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->connection = $resource->getConnection();
        $this->logger = $logger;
        $this->statusCollectionFactory = $statusCollectionFactory;

        $this->executors = [
            'customer' => $customer,
            'subscriber' => $subscriber,
            'product' => $product,
            'order' => $order
        ];
    }

    /**
     * Cron method synchronizing data by ids.
     * @throws \Synerise\ApiClient\ApiException
     */
    public function processByIds()
    {
        try {
            $statusCollection = $this->statusCollectionFactory->create()
                ->addFieldToFilter('state', StatusResourceModel::STATE_IN_PROGRESS)
                ->setPageSize(3);

            if (!$statusCollection->count()) {
                return;
            }

            foreach ($statusCollection as $statusItem) {
                $executor = $this->getExecutorByName($statusItem->getModel());
                if (!$executor || !$executor->isEnabled() || !in_array($statusItem->getStoreId(), $executor->getEnabledStores())) {
                    $statusItem
                        ->setState(StatusResourceModel::STATE_DISABLED)
                        ->save();
                    continue;
                }

                $stopId = $statusItem->getStopId();
                if (!$stopId) {
                    $stopId = $executor->getCurrentLastId($statusItem->getStoreId(), $statusItem->getWebsiteId());
                    $statusItem->setStopId($stopId);
                }

                $startId = $statusItem->getStartId();
                if ($startId == $stopId) {
                    $statusItem
                        ->setState(StatusResourceModel::STATE_COMPLETE)
                        ->save();
                    continue;
                }

                $collection = $executor->getCollectionFilteredByIdRange(
                    $statusItem->getStoreId(),
                    $statusItem->getWebsiteId(),
                    $statusItem->getStartId(),
                    $statusItem->getStopId()
                );

                if (!$collection->getSize()) {
                    $statusItem
                        ->setState(StatusResourceModel::STATE_COMPLETE)
                        ->save();
                    continue;
                }

                $executor->sendItems($collection, $statusItem->getStoreId(), $statusItem->getWebsiteId());

                $lastItem = $collection->getLastItem();
                $statusItem->setStartId($lastItem->getData($executor->getEntityIdField()));
                if ($startId == $stopId) {
                    $statusItem
                        ->setState(StatusResourceModel::STATE_COMPLETE);
                }

                $statusItem->save();

            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to process cron items', ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * @param String $name
     * @return AbstractSynchronization|null
     */
    public function getExecutorByName(String $name)
    {
        return $this->executors[$name] ?? null;
    }
}
