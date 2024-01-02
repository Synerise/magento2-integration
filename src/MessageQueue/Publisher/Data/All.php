<?php

namespace Synerise\Integration\MessageQueue\Publisher\Data;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class All extends AbstractBulk
{
    public const TYPE = 'all';

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
}
