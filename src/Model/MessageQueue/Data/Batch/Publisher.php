<?php

namespace Synerise\Integration\Model\MessageQueue\Data\Batch;


use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;

class Publisher
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
        $storeId,
        $entityIds,
        int $bulkSize = 100
    )
    {
        $entityIdsChunks = array_chunk($entityIds, $bulkSize);
        $bulkUuid = $this->identityService->generateId();
        $bulkDescription = __('Scheduled synchronization of %1 selected %2 items to Synerise', count($entityIds), $model);
        $operations = [];
        foreach ($entityIdsChunks as $entityIdsChunk) {
            $operations[] = $this->makeOperation(
                sprintf(self::TOPIC_FORMAT, $model),
                $storeId,
                $entityIdsChunk,
                $bulkUuid
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
     * @param string $topicName
     * @param int $storeId
     * @param int[] $entityIds
     * @param string $bulkUuid
     *
     * @return OperationInterface
     */
    private function makeOperation(
        string $topicName,
        int $storeId,
        array $entityIds,
        string $bulkUuid
    ): OperationInterface {
        $dataToEncode = [
            'entity_ids' => $entityIds,
            'store_id' => $storeId,
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
}
