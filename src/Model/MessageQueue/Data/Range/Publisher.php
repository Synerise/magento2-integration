<?php

namespace Synerise\Integration\Model\MessageQueue\Data\Range;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Authorization\Model\UserContextInterface;
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
        string $model,
        int $startId,
        int $stopId,
        int $storeId,
        int $websiteId = null,
        int $bulkSize = 100
    )
    {
        if (!$bulkSize || $startId > $stopId) {
            return false;
        }

        $ranges = $this->prepareRanges($startId, $stopId, $bulkSize);

        $bulkUuid = $this->identityService->generateId();
        $bulkDescription = __('Scheduled full synchronization of %1 items to Synerise', $model);
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
     * @param int $websiteId
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

    /**
     * @param int $gt
     * @param int $le
     * @param int $bulkSize
     * @return array
     */
    protected function prepareRanges(int $gt, int $le, int $bulkSize): array
    {
        $steps = range($gt, $le, $bulkSize);
        $ranges = [];

        while(current($steps) !== false && current($steps) !== $le) {
            $ranges[] = [
                'gt' => current($steps),
                'le' => next($steps) ?: $le
            ];
        }

        return $ranges;
    }
}
