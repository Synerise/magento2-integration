<?php

namespace Synerise\Integration\Model\Synchronization\MessageQueue\Data\Single;

use Magento\Framework\MessageQueue\PublisherInterface;

class Publisher
{
    /**
     * @var PublisherInterface
     */
    private $publisher;

    public function __construct(PublisherInterface $publisher)
    {
        $this->publisher = $publisher;
    }

    const TOPIC_NAME = 'synerise.queue.data.mixed.single';

    public function publish(string $model, int $entityId, int $storeId, ?int $websiteId = null, ?int $retries = 0)
    {
        $this->publisher->publish(self::TOPIC_NAME, new Message($model, $entityId, $storeId, $websiteId, $retries));
    }
}