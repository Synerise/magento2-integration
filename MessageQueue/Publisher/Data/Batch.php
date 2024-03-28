<?php

namespace Synerise\Integration\MessageQueue\Publisher\Data;

use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\SerializerInterface;
use Synerise\Integration\Model\ResourceModel\Bulk\CollectionFactory;

class Batch extends AbstractBulk
{
    public const TYPE = 'batch';
    /**
     * @var PublisherInterface
     */
    protected $publisher;

    /**
     * @param PublisherInterface $publisher
     * @param BulkManagementInterface $bulkManagement
     * @param IdentityGeneratorInterface $identityService
     * @param OperationInterfaceFactory $operationFactory
     * @param UserContextInterface $userContext
     * @param SerializerInterface $serializer
     */
    public function __construct(
        PublisherInterface $publisher,
        BulkManagementInterface $bulkManagement,
        IdentityGeneratorInterface $identityService,
        OperationInterfaceFactory $operationFactory,
        UserContextInterface $userContext,
        SerializerInterface $serializer
    ) {
        $this->publisher = $publisher;

        parent::__construct($bulkManagement, $identityService, $operationFactory, $userContext, $serializer);
    }

    /**
     * Consume message & Schedule batch synchronization
     *
     * @param string $model
     * @param array $entityIds
     * @param int $storeId
     * @param int|null $websiteId
     * @param int $bulkSize
     * @return void
     * @throws LocalizedException
     */
    public function schedule(
        string $model,
        array $entityIds,
        int $storeId,
        ?int $websiteId = null,
        int $bulkSize = 100
    ) {
        $entityIdsChunks = array_chunk($entityIds, $bulkSize);
        $bulkUuid = $this->identityService->generateId();
        $bulkDescription = $this->getBulKDescription(count($entityIds), $model, $storeId);
        $operations = [];
        foreach ($entityIdsChunks as $entityIdsChunk) {
            $operations[] = $this->makeOperation(
                $model,
                self::TYPE,
                $entityIdsChunk,
                $storeId,
                $bulkUuid,
                $websiteId
            );
        }

        if (!empty($operations)) {
            $result = $this->bulkManagement->scheduleBulk(
                $bulkUuid,
                $operations,
                $bulkDescription
            );
            if (!$result) {
                throw new LocalizedException(
                    __('Something went wrong while processing the request.')
                );
            }
        }
    }

    /**
     * Publish operation without bulk
     *
     * @param string $model
     * @param array $entityIds
     * @param int $storeId
     * @param int|null $websiteId
     * @param int $bulkSize
     * @return void
     */
    public function publish(
        string $model,
        array $entityIds,
        int $storeId,
        ?int $websiteId = null,
        int $bulkSize = 100
    ) {
        foreach (array_chunk($entityIds, $bulkSize) as $entityIdsChunk) {
            $operation = $this->makeOperation(
                $model,
                self::TYPE,
                $entityIdsChunk,
                $storeId,
                null,
                $websiteId
            );

            $this->publisher->publish($operation->getTopicName(), $operation);

        }
    }

    /**
     * Get Bulk description
     *
     * @param int $count
     * @param string $model
     * @param int $storeId
     * @return Phrase
     */
    public function getBulKDescription(int $count, string $model, int $storeId): Phrase
    {
        return __('Synerise: Batch %1 synchronization of %2 selected item(s) (Store id: %3)', $model, $count, $storeId);
    }
}
