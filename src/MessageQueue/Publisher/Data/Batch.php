<?php

namespace Synerise\Integration\MessageQueue\Publisher\Data;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;

class Batch
{
    const TOPIC_FORMAT = 'synerise.queue.data.%s.batch';

    /**
     * @var BulkManagementInterface
     */
    private $bulkManagement;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var IdentityGeneratorInterface
     */
    private $identityService;

    /**
     * @var OperationInterfaceFactory
     */
    private $operationFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

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

    public function schedule(
        $model,
        $entityIds,
        $storeId,
        $websiteId = null,
        int $bulkSize = 100
    )
    {
        $entityIdsChunks = array_chunk($entityIds, $bulkSize);
        $bulkUuid = $this->identityService->generateId();
        $bulkDescription = __('Synchronization of %1 selected %2(s) from store %3 to Synerise', count($entityIds), $model, $storeId);
        $operations = [];
        foreach ($entityIdsChunks as $entityIdsChunk) {
            $operations[] = $this->makeOperation(
                $bulkUuid,
                self::getTopicName($model),
                $entityIdsChunk,
                $storeId,
                $websiteId
            );
        }

        if (!empty($operations)) {
            $result = $this->bulkManagement->scheduleBulk(
                $bulkUuid,
                $operations,
                $bulkDescription,
                $this->userContext->getUserId()
            );
            if (!$result) {
                throw new LocalizedException(
                    __('Something went wrong while processing the request.')
                );
            }
        }
    }

    /**
     * Make asynchronous operation
     *
     * @param string $bulkUuid
     * @param string $topicName
     * @param int[] $entityIds
     * @param int $storeId
     * @param int|null $websiteId
     * @return OperationInterface
     */
    private function makeOperation(
        string $bulkUuid,
        string $topicName,
        array $entityIds,
        int $storeId,
        ?int $websiteId = null
    ): OperationInterface {
        $dataToEncode = [
            'bulk_uuid' => $bulkUuid,
            'entity_ids' => $entityIds,
            'store_id' => $storeId,
            'website_id' => $websiteId
        ];

        $operation = [
            'data' => [
                'bulk_uuid' => $bulkUuid,
                'topic_name' => $topicName,
                'serialized_data' => $this->serializer->serialize($dataToEncode),
                'status' => \Magento\Framework\Bulk\OperationInterface::STATUS_TYPE_OPEN,
            ]
        ];

        return $this->operationFactory->create($operation);
    }

    /**
     * @param string $model
     * @return string
     */
    public static function getTopicName(string $model): string
    {
        return sprintf(self::TOPIC_FORMAT, $model);
    }
}
