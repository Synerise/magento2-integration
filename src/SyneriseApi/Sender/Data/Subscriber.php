<?php

namespace Synerise\Integration\SyneriseApi\Sender\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Newsletter\Model\Subscriber as SubscriberModel;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection;
use Synerise\ApiClient\Api\DefaultApi;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Model\Workspace\ConfigFactory as WorkspaceConfigFactory;
use Synerise\Integration\SyneriseApi\Mapper\SubscriberAdd;
use Synerise\Integration\SyneriseApi\Sender\AbstractSender;
use Synerise\Integration\SyneriseApi\ConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory;

class Subscriber extends AbstractSender implements SenderInterface
{
    public const MODEL = 'subscriber';

    public const ENTITY_ID = 'subscriber_id';

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var SubscriberAdd
     */
    protected $subscriberAdd;


    /**
     * @param ResourceConnection $resource
     * @param ConfigFactory $configFactory
     * @param InstanceFactory $apiInstanceFactory
     * @param WorkspaceConfigFactory $workspaceConfigFactory
     * @param Logger $loggerHelper
     * @param SubscriberAdd $subscriberAdd
     */
    public function __construct(
        ResourceConnection $resource,
        ConfigFactory $configFactory,
        InstanceFactory $apiInstanceFactory,
        WorkspaceConfigFactory $workspaceConfigFactory,
        Logger $loggerHelper,
        SubscriberAdd $subscriberAdd
    ) {
        $this->connection = $resource->getConnection();
        $this->subscriberAdd = $subscriberAdd;

        parent::__construct($loggerHelper, $configFactory, $apiInstanceFactory, $workspaceConfigFactory);
    }

    /**
     * Send items
     *
     * @param Collection|SubscriberModel[] $collection
     * @param int $storeId
     * @param int|null $websiteId
     * @throws ApiException
     * @throws ValidatorException
     */
    public function sendItems($collection, int $storeId, ?int $websiteId = null)
    {
        $requests = [];
        $ids = [];

        foreach ($collection as $subscriber) {
            $requests[] = $this->subscriberAdd->prepareRequest($subscriber);
            $ids[] =  $subscriber->getId();
        }

        if (!empty($requests)) {
            $this->batchAddOrUpdateClients(
                $requests,
                $storeId
            );
            $this->markSubscribersAsSent($ids);
        }
    }

    /**
     * Delete items
     *
     * @param mixed $payload
     * @param int $storeId
     * @param int|null $entityId
     * @return void
     * @throws ApiException
     * @throws ValidatorException
     */
    public function deleteItem($payload, int $storeId, ?int $entityId = null)
    {
        $this->batchAddOrUpdateClients([$payload], $storeId);
        if ($entityId) {
            $this->deleteStatus([$entityId]);
        }
    }

    /**
     * Batch add or update clients
     *
     * @param mixed $payload
     * @param int $storeId
     * @throws ApiException
     * @throws ValidatorException
     */
    public function batchAddOrUpdateClients($payload, int $storeId)
    {
        try {
            list ($body, $statusCode, $headers) = $this->sendWithTokenExpiredCatch(
                function () use ($storeId, $payload) {
                    $this->getDefaultApiInstance($storeId)
                        ->batchAddOrUpdateClientsWithHttpInfo('application/json', '4.4', $payload);
                },
                $storeId
            );

            if ($statusCode == 207) {
                $this->loggerHelper->warning('Request partially accepted', ['response_body' => $body]);
            }
        } catch (ApiException $e) {
            $this->logApiException($e);
            throw $e;
        }
    }

    /**
     * Get Default API instance
     *
     * @param int $storeId
     * @return DefaultApi
     * @throws ApiException
     * @throws ValidatorException
     */
    protected function getDefaultApiInstance(int $storeId): DefaultApi
    {
        return $this->getApiInstance('default', $storeId);
    }

    /**
     * Mark subscribers as sent
     *
     * @param int[] $ids
     */
    public function markSubscribersAsSent($ids)
    {
        $data = [];
        foreach ($ids as $id) {
            $data[] = [
                'subscriber_id' => $id
            ];
        }

        $this->connection->insertOnDuplicate(
            $this->connection->getTableName('synerise_sync_subscriber'),
            $data
        );
    }

    /**
     * @inheritDoc
     */
    public function getAttributesToSelect(int $storeId): array
    {
        return [];
    }

    /**
     * Delete status
     *
     * @param int[] $entityIds
     * @return void
     */
    public function deleteStatus(array $entityIds)
    {
        $this->connection->delete(
            $this->connection->getTableName('synerise_sync_subscriber'),
            [
                'subscriber_id IN (?)' => $entityIds,
            ]
        );
    }
}
