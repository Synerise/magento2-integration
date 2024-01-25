<?php

namespace Synerise\Integration\MessageQueue\Publisher;

use Magento\Framework\App\Config\ScopeConfigInterface;
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

    /**
     * @param PublisherInterface $publisher
     * @param Json $json
     */
    public function __construct(
        PublisherInterface $publisher,
        Json $json
    ) {
        $this->publisher = $publisher;
        $this->json = $json;
    }

    public const TOPIC_NAME = 'synerise.queue.events';

    /**
     * Publish message to queue
     *
     * @param string $eventName
     * @param mixed $eventPayload
     * @param int $storeId
     * @param int|null $entityId
     * @param int $retries
     * @return void
     */
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
