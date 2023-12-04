<?php

namespace Synerise\Integration\MessageQueue\Publisher\Data;

use Magento\Framework\MessageQueue\PublisherInterface;

class Item
{
    /**
     * @var PublisherInterface
     */
    private $publisher;

    public function __construct(PublisherInterface $publisher)
    {
        $this->publisher = $publisher;
    }

    const TOPIC_NAME = 'synerise.queue.data.item';

    public function publish(string $model, int $entityId, int $storeId, ?int $websiteId = null, ?int $retries = 0)
    {
        $this->publisher->publish(self::TOPIC_NAME, new Message($model, $entityId, $storeId, $websiteId, $retries));
    }
}