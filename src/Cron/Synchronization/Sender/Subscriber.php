<?php

namespace Synerise\Integration\Cron\Synchronization\Sender;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\ValidatorException;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Update\ClientAgreement;
use Synerise\Integration\Model\ResourceModel\Cron\Queue as QueueResourceModel;

class Subscriber extends AbstractSender
{
    const MODEL = 'subscriber';
    const ENTITY_ID = 'subscriber_id';

    /**
     * @var ClientAgreement
     */
    protected $clientAgreementHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        ResourceConnection $resource,
        QueueResourceModel $queueResourceModel,
        CollectionFactory $collectionFactory,
        ClientAgreement $clientAgreementHelper
    ) {
        $this->clientAgreementHelper = $clientAgreementHelper;

        parent::__construct(
            $scopeConfig,
            $logger,
            $resource,
            $queueResourceModel,
            $collectionFactory
        );
    }

    public function getCollectionFilteredByIdRange($status)
    {
        $collection = parent::getCollectionFilteredByIdRange($status)
            ->addFieldToSelect(['subscriber_email', 'subscriber_status', 'change_status_at']);

        return $collection;
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
     * @param Collection $collection
     * @param int $storeId
     * @param int|null $websiteId
     * @return array|null
     * @throws ValidatorException
     * @throws ApiException
     */
    public function sendItems($collection, int $storeId, ?int $websiteId = null): ?array
    {
        if (!$collection->count()) {
            return null;
        }

        $requests = [];
        foreach ($collection as $subscriber) {
            $requests[] = $this->clientAgreementHelper->prepareCreateClientRequest($subscriber);
        }

        $response = $this->clientAgreementHelper->sendBatchAddOrUpdateClients($requests, $storeId);
        $this->clientAgreementHelper->markAsSent($collection->getAllIds());

        return $response;
    }

    public function markAllAsUnsent()
    {
        $this->connection->truncateTable($this->connection->getTableName('synerise_sync_subscriber'));
    }

    public function countAll()
    {
        $select = $this->connection->select()
            ->from($this->connection->getTableName('synerise_sync_subscriber'), 'COUNT(*)');

        return (int)$this->connection->fetchOne($select);
    }
}
