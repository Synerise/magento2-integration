<?php

namespace Synerise\Integration\SyneriseApi\Sender\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Newsletter\Model\Subscriber as SubscriberModel;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection;
use Synerise\ApiClient\Api\DefaultApi;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\Helper\Tracking\UuidGenerator;
use Synerise\Integration\Model\Workspace\ConfigFactory as WorkspaceConfigFactory;
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
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var UuidGenerator
     */
    protected $uuidGenerator;

    /**
     * @param ResourceConnection $resource
     * @param ConfigFactory $configFactory
     * @param InstanceFactory $apiInstanceFactory
     * @param WorkspaceConfigFactory $workspaceConfigFactory
     * @param Logger $loggerHelper
     * @param Tracking $trackingHelper
     * @param UuidGenerator $uuidGenerator
     */
    public function __construct(
        ResourceConnection $resource,
        ConfigFactory $configFactory,
        InstanceFactory $apiInstanceFactory,
        WorkspaceConfigFactory $workspaceConfigFactory,
        Logger $loggerHelper,
        Tracking $trackingHelper,
        UuidGenerator $uuidGenerator
    ) {
        $this->connection = $resource->getConnection();
        $this->trackingHelper = $trackingHelper;
        $this->uuidGenerator = $uuidGenerator;

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
            $requests[] = $this->prepareRequestFromSubscription($subscriber);
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
                $this->loggerHelper->getLogger()->warning('Request partially accepted', ['response_body' => $body]);
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
     * Prepare request from subscription
     *
     * @param SubscriberModel $subscriber
     * @return CreateaClientinCRMRequest
     */
    public function prepareRequestFromSubscription(SubscriberModel $subscriber): CreateaClientinCRMRequest
    {
        $email = $subscriber->getSubscriberEmail();
        return new CreateaClientinCRMRequest(
            [
                'email' => $email,
                'uuid' => $this->uuidGenerator->generateByEmail($email),
                'agreements' => [
                    'email' => $subscriber->getSubscriberStatus() == SubscriberModel::STATUS_SUBSCRIBED ? 1 : 0
                ]
            ]
        );
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
