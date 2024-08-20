<?php

namespace Synerise\Integration\SyneriseApi\Sender\Data;

use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Synerise\ApiClient\Api\DefaultApi;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking\UuidGenerator;
use Synerise\Integration\Model\Workspace\ConfigFactory as WorkspaceConfigFactory;
use Synerise\Integration\SyneriseApi\Mapper\Data\OrderCRUD;
use Synerise\Integration\SyneriseApi\Sender\AbstractSender;
use Synerise\Integration\SyneriseApi\ConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory;

class Order extends AbstractSender implements SenderInterface
{
    public const MODEL = 'order';

    public const ENTITY_ID = 'entity_id';

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var OrderCRUD
     */
    protected $orderCRUD;

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
     * @param OrderCRUD $orderCRUD
     * @param UuidGenerator $uuidGenerator
     */
    public function __construct(
        ResourceConnection $resource,
        ConfigFactory $configFactory,
        InstanceFactory $apiInstanceFactory,
        WorkspaceConfigFactory $workspaceConfigFactory,
        Logger $loggerHelper,
        OrderCRUD $orderCRUD,
        UuidGenerator $uuidGenerator
    ) {
        $this->resource = $resource;
        $this->orderCRUD = $orderCRUD;
        $this->uuidGenerator = $uuidGenerator;

        parent::__construct($loggerHelper, $configFactory, $apiInstanceFactory, $workspaceConfigFactory);
    }

    /**
     * Send items
     *
     * @param Collection|OrderModel[] $collection
     * @param int $storeId
     * @param int|null $websiteId
     * @param array $options
     * @return void
     * @throws ApiException
     * @throws ValidatorException
     * @throws Exception
     */
    public function sendItems($collection, int $storeId, ?int $websiteId = null, array $options = [])
    {
        $ids = $createATransactionRequest = [];

        foreach ($collection as $order) {
            $ids[] = $order->getEntityId();

            $email = $order->getCustomerEmail();
            $uuid = $email ? $this->uuidGenerator->generateByEmail($email) : null;
            $snrsParams = isset($options['snrs_params']) && is_array($options['snrs_params']) ?
                $options['snrs_params'] : [];

            try {
                $createATransactionRequest[] = $this->orderCRUD->prepareRequest($order, $uuid, $snrsParams);
            } catch (NotFoundException $e) {
                if ($this->isSent($order->getEntityId())) {
                    $this->loggerHelper->warning(
                        sprintf('Product not found & order %s already sent, skip update', $order->getIncrementId())
                    );
                } else {
                    $this->loggerHelper->error($e);
                }

            }
        }

        if (!empty($createATransactionRequest)) {
            $this->batchAddOrUpdateTransactions(
                $createATransactionRequest,
                $storeId
            );
        }

        if ($ids) {
            $this->markItemsAsSent($ids, $storeId);
        }
    }

    /**
     * Batch add or update transactions
     *
     * @param mixed $payload
     * @param int $storeId
     * @throws ApiException
     * @throws ValidatorException
     */
    public function batchAddOrUpdateTransactions($payload, int $storeId)
    {
        try {
            list ($body, $statusCode, $headers) = $this->sendWithTokenExpiredCatch(
                function () use ($storeId, $payload) {
                    $this->getDefaultApiInstance($storeId)
                        ->batchAddOrUpdateTransactionsWithHttpInfo('4.4', $payload);
                },
                $storeId
            );

            if ($statusCode == 207) {
                $this->loggerHelper->warning('Request partially accepted', ['response' => $body]);
            }
        } catch (ApiException $e) {
            $this->logApiException($e);
            throw $e;
        }
    }

    /**
     * Check if order is sent
     *
     * @param int $orderId
     * @return string
     */
    public function isSent(int $orderId): string
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select();
        $tableName = $connection->getTableName('synerise_sync_order');

        $select->from($tableName, ['synerise_updated_at'])
            ->where('order_id = ?', $orderId);

        return $connection->fetchOne($select);
    }

    /**
     * Mark orders as sent
     *
     * @param int[] $ids
     * @param int $storeId
     * @return void
     */
    public function markItemsAsSent(array $ids, int $storeId)
    {
        $data = [];
        foreach ($ids as $id) {
            $data[] = [
                'order_id' => $id,
                'store_id' => $storeId
            ];
        }
        $this->resource->getConnection()->insertOnDuplicate(
            $this->resource->getConnection()->getTableName('synerise_sync_order'),
            $data
        );
    }

    /**
     * @inheritDoc
     */
    public function getAttributesToSelect(int $storeId): array
    {
        return[];
    }

    /**
     * Get default API instance
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
}
