<?php

namespace Synerise\Integration\Cron\Synchronization\Sender;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Identity;
use Synerise\Integration\Helper\Update\Transaction;
use Synerise\Integration\Model\ResourceModel\Cron\Queue as QueueResourceModel;

class Order extends AbstractSender
{
    const MODEL = 'order';
    const ENTITY_ID = 'entity_id';

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var Transaction
     */
    protected $orderHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        ResourceConnection $resource,
        QueueResourceModel $queueResourceModel,
        CollectionFactory $collectionFactory,
        Transaction $orderHelper,
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

    /**
     * @param $collection
     * @param int $storeId
     * @param int|null $websiteId
     * @return void
     * @throws ValidatorException
     * @throws ApiException
     * @throws \Exception
     */
    public function sendItems($collection, int $storeId, ?int $websiteId = null): ?array
    {
        $collection->addAttributeToSelect('*');

        if (!$collection->getSize()) {
            return null;
        }

        $ids = [];
        $createTransactionRequest = [];
        foreach ($collection as $order) {
            $ids[] = $order->getId();
            $email = $order->getCustomerEmail();
            $uuid = $email ? Identity::generateUuidByEmail($email): null;
            $request = $this->orderHelper->prepareCreateTransactionRequest($order, $uuid);
            if ($request) {
                $createTransactionRequest[] = $request;
            }
        }

        if (!empty($createTransactionRequest)) {
            $response = $this->orderHelper->sendBatchAddOrUpdateTransactions($createTransactionRequest, $storeId);
            $this->orderHelper->markAsSent($ids);
            return $response;
        }

        return null;
    }

    public function markAllAsUnsent()
    {
        $this->connection->truncateTable($this->connection->getTableName('synerise_sync_order'));
    }
}
