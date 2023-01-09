<?php

namespace Synerise\Integration\Model\Synchronization;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Identity;
use Synerise\Integration\Helper\Update\Order as OrderHelper;
use Synerise\Integration\Model\AbstractSynchronization;
use Synerise\Integration\Model\ResourceModel\Cron\Queue as QueueResourceModel;

class Order extends AbstractSynchronization
{
    const MODEL = 'order';
    const ENTITY_ID = 'entity_id';

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        ResourceConnection $resource,
        QueueResourceModel $queueResourceModel,
        CollectionFactory $collectionFactory,
        OrderHelper $orderHelper,
        DateTime $dateTime
    ) {
        $this->orderHelper = $orderHelper;
        $this->dateTime = $dateTime;

        parent::__construct(
            $scopeConfig,
            $logger,
            $resource,
            $queueResourceModel,
            $collectionFactory
        );
    }

    /**
     * @param int $storeId
     * @param int|null $websiteId
     * @return mixed
     */
    protected function createCollectionWithScope($storeId, $websiteId = null)
    {
        $collection = $this->collectionFactory->create();
        $collection->getSelect()
            ->where('main_table.store_id=?', $storeId);

        return $collection;
    }

    public function sendItems($collection, $storeId, $websiteId = null)
    {
        $collection->addAttributeToSelect('*');

        if (!$collection->getSize()) {
            return;
        }

        $createTransactionRequest = [];
        foreach ($collection as $order) {
            $email = $order->getCustomerEmail();
            $uuid = $email ? Identity::generateUuidByEmail($email): null;
            $request = $this->orderHelper->prepareCreateTransactionRequest($order, $uuid);
            if ($request) {
                $createTransactionRequest[] = $request;
            }
        }

        if (!empty($createTransactionRequest)) {
            $this->orderHelper->sendBatchAddOrUpdateTransactions($createTransactionRequest, $storeId);
        }

        $this->orderHelper->markItemsAsSent($collection->getAllIds());
    }

    public function markAllAsUnsent()
    {
        $this->connection->truncateTable($this->connection->getTableName('synerise_sync_order'));
    }
}
