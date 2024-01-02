<?php

namespace Synerise\Integration\MessageQueue\Publisher\Data;

use Magento\Framework\MessageQueue\PublisherInterface;
use Synerise\Integration\MessageQueue\Message\Data\Item as MessageItem;

class Item
{
    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param PublisherInterface $publisher
     */
    public function __construct(PublisherInterface $publisher)
    {
        $this->publisher = $publisher;
    }

    public const TOPIC_NAME = 'synerise.queue.data.item';

    /**
     * Publish message to queue
     *
     * @param string $model
     * @param int $entityId
     * @param int $storeId
     * @param int|null $websiteId
     * @param int|null $retries
     * @return void
     */
    public function publish(string $model, int $entityId, int $storeId, ?int $websiteId = null, ?int $retries = 0)
    {
        $this->publisher->publish(self::TOPIC_NAME, new MessageItem($model, $entityId, $storeId, $websiteId, $retries));
    }
}
