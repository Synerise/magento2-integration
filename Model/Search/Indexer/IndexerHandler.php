<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Synerise\Integration\Model\Search\Indexer;

use Magento\Framework\Indexer\SaveHandler\IndexerInterface;

/**
 * Mock Indexer Handler for Synerise AI engine.
 * Synerise uses its own external indexes.
 * Since catalog_search index can not be fully disabled this is just a mock of a handler.
 */
class IndexerHandler implements IndexerInterface
{
    /**
     * @inheritdoc
     */
    public function saveIndex($dimensions, \Traversable $documents)
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function deleteIndex($dimensions, \Traversable $documents)
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function cleanIndex($dimensions)
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isAvailable($dimensions = [])
    {
        return true;
    }
}
