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
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\MessageQueue\Sender\AbstractSender;
use Synerise\Integration\SyneriseApi\ConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory;

class Subscriber extends AbstractSender implements SenderInterface
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
     * @var Tracking
     */
    protected $trackingHelper;

    public function __construct(
        ResourceConnection $resource,
        LoggerInterface $logger,
        ConfigFactory $configFactory,
        InstanceFactory $apiInstanceFactory,
        Tracking $trackingHelper
    ) {
        $this->connection = $resource->getConnection();
        $this->logger = $logger;
        $this->trackingHelper = $trackingHelper;

        parent::__construct($logger, $configFactory, $apiInstanceFactory);
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
                $storeId
            );
            $this->markSubscribersAsSent($ids);
        }
    }

    /**
     * @param $createAClientInCrmRequests
     * @param $storeId
     * @param bool $isRetry
     * @throws ApiException
     * @throws ValidatorException
     */
    public function batchAddOrUpdateClients($createAClientInCrmRequests, $storeId, $isRetry = false)
    {
        try {
            list ($body, $statusCode, $headers) = $this->getDefaultApiInstance($storeId)
                ->batchAddOrUpdateClientsWithHttpInfo('application/json', '4.4', $createAClientInCrmRequests);

            if (substr($statusCode, 0, 1) != 2) {
                throw new ApiException(sprintf('Invalid Status [%d]', $statusCode));
            } elseif ($statusCode == 207) {
                $this->logger->warning('Request partially accepted', ['response_body' => $body]);
            }
        } catch (ApiException $e) {
            $this->handleApiExceptionAndMaybeUnsetToken($e, ConfigFactory::MODE_SCHEDULE, $storeId);
            if (!$isRetry) {
                $this->batchAddOrUpdateClients($createAClientInCrmRequests, $storeId, true);
            }
        }
    }

    /**
     * @param int $storeId
     * @return mixed
     * @throws ApiException
     * @throws ValidatorException
     */
    protected function getDefaultApiInstance(int $storeId)
    {
        $config = $this->configFactory->getConfig(ConfigFactory::MODE_SCHEDULE, $storeId);
        return $this->apiInstanceFactory->getApiInstance(
            $config->getScopeKey(),
            'default',
            $config
        );
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
