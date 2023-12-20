<?php

namespace Synerise\Integration\MessageQueue\Publisher\Data;

use Magento\Framework\Exception\LocalizedException;

class Batch extends AbstractBulk
{
    const TYPE = 'batch';

    public function schedule(
        string $model,
        array $entityIds,
        int $storeId,
        ?int $websiteId = null,
        ?int $userId = null,
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
                $userId ?: $this->userContext->getUserId()
            );
            if (!$result) {
                throw new LocalizedException(
                    __('Something went wrong while processing the request.')
                );
            }
        }
    }

}
