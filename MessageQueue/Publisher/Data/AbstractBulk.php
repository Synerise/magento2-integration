<?php

namespace Synerise\Integration\MessageQueue\Publisher\Data;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\SerializerInterface;

abstract class AbstractBulk
{
    public const TOPIC_FORMAT = 'synerise.queue.data.%s.%s.%s';

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

    /**
     * @param BulkManagementInterface $bulkManagement
     * @param IdentityGeneratorInterface $identityService
     * @param OperationInterfaceFactory $operationFactory
     * @param UserContextInterface $userContext
     * @param SerializerInterface $serializer
     */
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
     * @param string $model
     * @param string $type
     * @param int[] $entityIds
     * @param int $storeId
     * @param string|null $bulkUuid
     * @param int|null $websiteId
     * @return OperationInterface
     */
    protected function makeOperation(
        string $model,
        string $type,
        array $entityIds,
        int $storeId,
        ?string $bulkUuid = null,
        ?int $websiteId = null
    ): OperationInterface {
        $dataToEncode = [
            'type' => $type,
            'model' => $model,
            'entity_ids' => $entityIds,
            'store_id' => $storeId,
            'website_id' => $websiteId
        ];

        if ($bulkUuid) {
            $dataToEncode['bulk_uuid'] = $bulkUuid;
            $data = [
                'bulk_uuid' => $bulkUuid,
                'topic_name' => self::getTopicName($model, $type, $storeId),
                'serialized_data' => $this->serializer->serialize($dataToEncode),
                'status' => \Magento\Framework\Bulk\OperationInterface::STATUS_TYPE_OPEN,
            ];
        } else {
            $data = [
                'topic_name' => self::getTopicName($model, $type, $storeId),
                'serialized_data' => $this->serializer->serialize($dataToEncode),
                'status' => \Magento\Framework\Bulk\OperationInterface::STATUS_TYPE_OPEN
            ];
        }

        $operation = [
            'data' => $data
        ];

        return $this->operationFactory->create($operation);
    }

    /**
     * Get topic name
     *
     * @param string $model
     * @param string $type
     * @param int|null $storeId
     * @return string
     */
    // phpcs:ignore
    public static function getTopicName(string $model, string $type, int $storeId): string
    {
        return sprintf(self::TOPIC_FORMAT, $type, $model, $storeId);
    }
}
