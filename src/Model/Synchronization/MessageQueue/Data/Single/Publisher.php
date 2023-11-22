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

    public function publish(string $model, int $storeId, int $entityId, $retries = 0)
    {
        $this->publisher->publish(self::TOPIC_NAME, new Message($model,  $storeId,  $entityId,  $retries));
    }
}