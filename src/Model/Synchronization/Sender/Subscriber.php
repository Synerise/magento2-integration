<?php

namespace Synerise\Integration\Model\Synchronization\Sender;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Newsletter\Model\Subscriber as SubscriberModel;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Model\Synchronization\SenderInterface;

class Subscriber implements SenderInterface
{
    const MODEL = 'subscriber';
    const ENTITY_ID = 'subscriber_id';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

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
        ScopeConfigInterface $scopeConfig,
        AdapterInterface $connection,
        LoggerInterface $logger,
        Api $apiHelper,
        Tracking $trackingHelper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
        $this->trackingHelper = $trackingHelper;
    }

    /**
     * @param Collection $collection
     * @param int $storeId
     * @param int|null $websiteId
     * @throws ApiException
     * @throws ValidatorException
     */
    public function sendItems($collection, int $storeId, ?int $websiteId = null)
    {
        if (!$collection->count()) {
            return;
        }

        $requests = [];
        foreach ($collection as $subscriber) {
            $requests[] = $this->prepareRequestFromSubscription($subscriber);
        }

        $this->batchAddOrUpdateClients(
            $requests,
            $storeId,
            $this->apiHelper->getScheduledRequestTimeout($storeId)
        );
        $this->markSubscribersAsSent($collection->getAllIds());
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
     * @param int|null $storeId
     * @return int
     */
    public function getPageSize(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            Synchronization::XML_PATH_CRON_STATUS_PAGE_SIZE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
