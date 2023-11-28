<?php

namespace Synerise\Integration\Model\Synchronization\MessageQueue\Data\Scheduler;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;

class Publisher
{
    const TOPIC_NAME = 'synerise.queue.data.scheduler';

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
        $storeIds
    )
    {
        $bulkUuid = $this->identityService->generateId();
        $bulkDescription = __('%1 full synchronization request.', ucfirst($model));
        $operations = [];
        foreach ($storeIds as $storeId) {
            $operations[] = $this->makeOperation(
                $model,
                $storeId,
                $bulkUuid,
                $this->userContext->getUserId()
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
     * @param string $model
     * @param int $storeId
     * @param string $bulkUuid
     * @param int $userId
     * @return OperationInterface
     */
    private function makeOperation(
        string $model,
        int $storeId,
        string $bulkUuid,
        int $userId
    ): OperationInterface {
        $dataToEncode = [
            'model' => $model,
            'store_id' => $storeId,
            'user_id' => $userId
        ];

        $operation = [
            'data' => [
                'bulk_uuid' => $bulkUuid,
                'topic_name' => self::TOPIC_NAME,
                'serialized_data' => $this->serializer->serialize($dataToEncode),
                'status' => \Magento\Framework\Bulk\OperationInterface::STATUS_TYPE_OPEN,
            ]
        ];

        return $this->operationFactory->create($operation);
    }
}
