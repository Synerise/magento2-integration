<?php

namespace Synerise\Integration\MessageQueue\Sender\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Newsletter\Model\Subscriber as SubscriberModel;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Tracking;

class Subscriber implements SenderInterface
{
    const MODEL = 'subscriber';
    const ENTITY_ID = 'subscriber_id';

    const MAX_PAGE_SIZE = 500;

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Api
     */
    protected $apiHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    public function __construct(
        ResourceConnection $resource,
        LoggerInterface $logger,
        Api $apiHelper,
        Tracking $trackingHelper
    ) {
        $this->connection = $resource->getConnection();
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
        $this->trackingHelper = $trackingHelper;
    }

    /**
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
                $storeId,
                $this->apiHelper->getScheduledRequestTimeout($storeId)
            );
            $this->markSubscribersAsSent($ids);
        }
    }

    /**
     * @param $createAClientInCrmRequests
     * @param $storeId
     * @param null $timeout
     * @throws ApiException
     * @throws ValidatorException
     */
    public function batchAddOrUpdateClients($createAClientInCrmRequests, $storeId, $timeout = null)
    {
        list ($body, $statusCode, $headers) = $this->apiHelper->getDefaultApiInstance($storeId, $timeout)
            ->batchAddOrUpdateClientsWithHttpInfo('application/json', '4.4', $createAClientInCrmRequests);

        if (substr($statusCode, 0, 1) != 2) {
            throw new ApiException(sprintf('Invalid Status [%d]', $statusCode));
        } elseif ($statusCode == 207) {
            $this->logger->warning('Request partially accepted', ['response' => $body]);
        }
    }

    /**
     * @param SubscriberModel $subscriber
     * @return CreateaClientinCRMRequest
     */
    public function prepareRequestFromSubscription($subscriber)
    {
        $email = $subscriber->getSubscriberEmail();
        return new CreateaClientinCRMRequest(
            [
                'email' => $email,
                'uuid' => $this->trackingHelper->generateUuidByEmail($email),
                'agreements' => [
                    'email' => $subscriber->getSubscriberStatus() == SubscriberModel::STATUS_SUBSCRIBED ? 1 : 0
                ]
            ]
        );
    }

    /**
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
