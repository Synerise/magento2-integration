<?php

namespace Synerise\Integration\MessageQueue\Publisher\Data;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Serialize\SerializerInterface;

abstract class AbstractBulk
{
    const TOPIC_FORMAT = 'synerise.queue.data.%s.%s.%s';

    /**
     * @var BulkManagementInterface
     */
    protected $bulkManagement;

    /**
     * @var UserContextInterface
     */
    protected $userContext;

    /**
     * @var IdentityGeneratorInterface
     */
    protected $identityService;

    /**
     * @var OperationInterfaceFactory
     */
    protected $operationFactory;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    public function __construct(
        BulkManagementInterface $bulkManagement,
        IdentityGeneratorInterface $identityService,
        OperationInterfaceFactory $operationFactory,
        UserContextInterface $userContext,
        SerializerInterface $serializer
    ) {
        $this->bulkManagement = $bulkManagement;
        $this->userContext = $userContext;
        $this->identityService = $identityService;
        $this->operationFactory = $operationFactory;
        $this->serializer = $serializer;
    }

    /**
     * Make asynchronous operation
     *
     * @param string $bulkUuid
     * @param string $model
     * @param string $type
     * @param int[] $entityIds
     * @param int $storeId
     * @param int|null $websiteId
     * @return OperationInterface
     */
    protected function makeOperation(
        string $bulkUuid,
        string $model,
        string $type,
        array $entityIds,
        int $storeId,
        ?int $websiteId = null
    ): OperationInterface {
        $dataToEncode = [
            'bulk_uuid' => $bulkUuid,
            'type' => $type,
            'model' => $model,
            'entity_ids' => $entityIds,
            'store_id' => $storeId,
            'website_id' => $websiteId
        ];

        $operation = [
            'data' => [
                'bulk_uuid' => $bulkUuid,
                'topic_name' => self::getTopicName($model, $type, $storeId),
                'serialized_data' => $this->serializer->serialize($dataToEncode),
                'status' => \Magento\Framework\Bulk\OperationInterface::STATUS_TYPE_OPEN,
            ]
        ];

        return $this->operationFactory->create($operation);
    }

    /**
     * @param string $model
     * @param string $type
     * @param int|null $storeId
     * @return string
     */
    public static function getTopicName(string $model, string $type, int $storeId): string
    {
        return sprintf(self::TOPIC_FORMAT, $type, $model, $storeId);
    }
}
