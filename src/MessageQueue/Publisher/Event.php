<?php

namespace Synerise\Integration\MessageQueue\Publisher;

use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;

class Event
{
    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var Json
     */
    private $json;

    public function __construct(
        PublisherInterface $publisher,
        Json $json
    ) {
        $this->publisher = $publisher;
        $this->json = $json;
    }

    const TOPIC_NAME = 'synerise.queue.events';

    public function publish(string $eventName, $eventPayload, int $storeId, int $entityId = null, int $retries = 0)
    {
        $this->publisher->publish(
            self::TOPIC_NAME,
            $this->json->serialize([
                'event_name' => $eventName,
                'event_payload' => $eventPayload,
                'store_id' => $storeId,
                'entity_id' => $entityId,
                'retries' => $retries
            ])
        );
    }
}
