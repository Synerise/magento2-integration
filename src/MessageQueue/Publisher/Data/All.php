<?php

namespace Synerise\Integration\MessageQueue\Publisher\Data;

use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\Bulk\OperationInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\SerializerInterface;
use Synerise\Integration\Model\Bulk as BulkModel;
use Synerise\Integration\Model\ResourceModel\Bulk\CollectionFactory;

class All extends AbstractBulk
{
    public const TYPE = 'all';

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var CollectionFactory
     */
    protected $bulkCollectionFactory;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param BulkManagementInterface $bulkManagement
     * @param IdentityGeneratorInterface $identityService
     * @param OperationInterfaceFactory $operationFactory
     * @param UserContextInterface $userContext
     * @param SerializerInterface $serializer
     * @param CollectionFactory $bulkCollectionFactory
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        BulkManagementInterface $bulkManagement,
        IdentityGeneratorInterface $identityService,
        OperationInterfaceFactory $operationFactory,
        UserContextInterface $userContext,
        SerializerInterface $serializer,
        CollectionFactory $bulkCollectionFactory
    ) {
        $this->objectManager = $objectManager;
        $this->bulkCollectionFactory = $bulkCollectionFactory;

        parent::__construct($bulkManagement, $identityService, $operationFactory, $userContext, $serializer);
    }

    /**
     * Consume message & Schedule full synchronization
     *
     * @param int $userId
     * @param string $model
     * @param array $entityIds
     * @param int $storeId
     * @param int|null $websiteId
     * @return void
     * @throws LocalizedException
     */
    public function schedule(
        int $userId,
        string $model,
        array $entityIds,
        int $storeId,
        ?int $websiteId = null
    ) {
        $bulkUuid = $this->identityService->generateId();
        $bulkDescription = $this->getBulKDescription($model, $storeId);
        $operations = [];

        foreach ($entityIds as $entityIdsChunk) {
            $operations[] = $this->makeOperation(
                $bulkUuid,
                $model,
                self::TYPE,
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
                $userId
            );
            if (!$result) {
                throw new LocalizedException(
                    __('Something went wrong while processing the request.')
                );
            }
        }

        $this->cancelPreviousBulks($model, $storeId);
        $this->saveBulk($bulkUuid, $model, $storeId);
    }

    /**
     * Get bulk description
     *
     * @param string $model
     * @param int $storeId
     * @return Phrase
     */
    public function getBulKDescription(string $model, int $storeId): Phrase
    {
        return __('Synerise: Full %1 synchronization (Store id: %2)', $model, $storeId);
    }

    /**
     * Save scheduled Bulk
     *
     * @param string $bulkUuid
     * @param string $model
     * @param int $storeId
     * @return void
     */
    protected function saveBulk(string $bulkUuid, string $model, int $storeId)
    {
        /** @var BulkModel $bulk */
        $bulk = $this->objectManager->create(BulkModel::class);

        $bulk
            ->setUuid($bulkUuid)
            ->setModel($model)
            ->setStoreId($storeId)
            ->setStatus(OperationInterface::STATUS_TYPE_OPEN)
            ->save();
    }

    /**
     * Mark previous bulks with "To Be Canceled" status
     *
     * @param string $model
     * @param int $storeId
     * @return void
     */
    protected function cancelPreviousBulks(string $model, int $storeId)
    {
        $collection = $this->bulkCollectionFactory->create()
            ->addFieldToFilter('status', ['eq' => OperationInterface::STATUS_TYPE_OPEN])
            ->addFieldToFilter('model', ['eq' => $model])
            ->addFieldToFilter('store_id', ['eq' => $storeId]);

        foreach ($collection as $bulk) {
            $bulk->setStatus(BulkModel::STATUS_TYPE_TO_BE_CANCELED)->save();
        }
    }
}
