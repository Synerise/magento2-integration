<?php

namespace Synerise\Integration\Helper\Update;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Newsletter\Model\Subscriber;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\Integration\Helper\AbstractDefaultApiAction;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Identity;


class ClientAgreement extends AbstractDefaultApiAction
{

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        ResourceConnection $resource,
        DateTime $dateTime,
        LoggerInterface $logger,
        Api $apiHelper,
        Api\DefaultApiFactory $defaultApiFactory
    ) {
        $this->connection = $resource->getConnection();
        $this->dateTime = $dateTime;
        $this->logger = $logger;

        parent::__construct($apiHelper, $defaultApiFactory);
    }

    /**
     * @param CreateaClientinCRMRequest[] $createAClientInCrmRequests
     * @param int|null $storeId
     * @return array
     * @throws ApiException
     */
    public function sendBatchAddOrUpdateClients(array $createAClientInCrmRequests, int $storeId = null)
    {
        list ($body, $statusCode, $headers) = $this->getDefaultApiInstance($storeId)
            ->batchAddOrUpdateClientsWithHttpInfo('application/json', '4.4', $createAClientInCrmRequests);

        if (substr($statusCode, 0, 1) != 2) {
            throw new ApiException(sprintf('Client agreements - Invalid Status [%d]', $statusCode));
        } elseif ($statusCode == 207) {
            $this->logger->debug('Client agreements - Request accepted with errors', ['response' => $body]);
        }

        return [$body, $statusCode, $headers];
    }

    /**
     * @param Subscriber $subscriber
     * @return CreateaClientinCRMRequest
     */
    public function prepareCreateClientRequest($subscriber)
    {
        $email = $subscriber->getSubscriberEmail();
        return new CreateaClientinCRMRequest(
            [
                'email' => $email,
                'uuid' => Identity::generateUuidByEmail($email),
                'agreements' => [
                    'email' => $subscriber->getSubscriberStatus() == Subscriber::STATUS_SUBSCRIBED ? 1 : 0
                ]
            ]
        );
    }

    public function markAsSent($ids)
    {
        $timestamp = $this->dateTime->gmtDate();
        $data = [];
        foreach ($ids as $id) {
            $data[] = [
                'synerise_updated_at' => $timestamp,
                'subscriber_id' => $id
            ];
        }

        $this->connection->insertOnDuplicate(
            $this->connection->getTableName('synerise_sync_subscriber'),
            $data
        );
    }
}
