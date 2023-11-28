<?php

namespace Synerise\Integration\Model\Synchronization\MessageQueue\Data\Range;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;

class Publisher
{
    const TOPIC_FORMAT = 'synerise.queue.data.%s.range';

    /**
     * @var BulkManagementInterface
     */
    private $bulkManagement;

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
        SerializerInterface $serializer
    ) {
        $this->bulkManagement = $bulkManagement;
        $this->identityService = $identityService;
        $this->operationFactory = $operationFactory;
        $this->serializer = $serializer;
    }

    public function schedule(
        int $userId,
        string $model,
        array $ranges,
        int $storeId,
        ?int $websiteId
    )
    {
        $bulkUuid = $this->identityService->generateId();
        $bulkDescription = __('Scheduled full %1 synchronization to Synerise (Store id: %2)', $model, $storeId);

        $operations = [];
        foreach ($ranges as $range) {
            $operations[] = $this->makeOperation(
                sprintf(self::TOPIC_FORMAT, $model),
                $storeId,
                $websiteId,
                $range['gt'],
                $range['le'],
                $bulkUuid
            );
        }

        if (!empty($operations)) {
            $result = $this->bulkManagement->scheduleBulk(
                $bulkUuid,
                $operations,
                $bulkDescription,
                $userId
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
     * @param int|null $websiteId
     * @param int $gt
     * @param int $le
     * @param string $bulkUuid
     *
     * @return OperationInterface
     */
    private function makeOperation(
        string $topicName,
        int $storeId,
        ?int $websiteId,
        int $gt,
        int $le,
        string $bulkUuid
    ): OperationInterface {
        $dataToEncode = [
            'gt' => $gt,
            'le' => $le,
            'store_id' => $storeId,
            'website_id' => $websiteId,
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
