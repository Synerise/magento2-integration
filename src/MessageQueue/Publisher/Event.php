<?php

namespace Synerise\Integration\MessageQueue\Publisher;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;

class Event
{
    public const XML_PATH_QUEUE_ENABLED = 'synerise/queue/enabled';

    public const XML_PATH_QUEUE_EVENTS = 'synerise/queue/events';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var Json
     */
    private $json;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param PublisherInterface $publisher
     * @param Json $json
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        PublisherInterface $publisher,
        Json $json
    ) {
        $this->scopeConfig = $scopeConfig;
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

    /**
     * Check if event should be sent via Message Queue
     *
     * @param string $event
     * @param int|null $storeId
     * @return bool
     */
    public function isEventMessageQueueAvailable(string $event, int $storeId = null): bool
    {
        if (!$this->isEventMessageQueueEnabled($storeId)) {
            return false;
        }

        return $this->isEventSelectedForMessageQueue($event, $storeId);
    }

    /**
     * Check if message queue is enabled for events
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEventMessageQueueEnabled(int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_QUEUE_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if event is selected to be sent via Message Queue
     *
     * @param string $event
     * @param int|null $storeId
     * @return bool
     */
    protected function isEventSelectedForMessageQueue(string $event, ?int $storeId = null): bool
    {
        $events = explode(',', $this->scopeConfig->getValue(
            self::XML_PATH_QUEUE_EVENTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        return in_array($event, $events);
    }
}
