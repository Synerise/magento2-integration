<?php

namespace Synerise\Integration\MessageQueue\Publisher\Data;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class Batch extends AbstractBulk
{
    public const TYPE = 'batch';

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
