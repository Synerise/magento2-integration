<?php

namespace Synerise\Integration\Model\Tracking;

class Config
{
    public const XML_PATH_EVENT_TRACKING_ENABLED = 'synerise/event_tracking/enabled';

    public const XML_PATH_EVENT_TRACKING_EVENTS = 'synerise/event_tracking/events';

    public const XML_PATH_QUEUE_ENABLED = 'synerise/queue/enabled';

    public const XML_PATH_QUEUE_EVENTS = 'synerise/queue/events';

    /**
     * @var Config\Data
     */
    protected $dataStorage;

    /**
     * @param Config\Data $dataStorage
     */
    public function __construct(Config\Data $dataStorage)
    {
        $this->dataStorage = $dataStorage;
    }

    /**
     * Check if event tracking is enabled & event should be tracked
     *
     * @param int $storeId
     * @param string|null $eventName
     * @return bool
     */
    public function isEventTrackingEnabled(int $storeId, ?string $eventName = null): bool
    {
        if ($eventName) {
            return $this->dataStorage->getByScope(
                $storeId,
                self::XML_PATH_EVENT_TRACKING_EVENTS . '/' . $eventName,
                false
            );
        } else {
            return $this->dataStorage->getByScope(
                $storeId,
                self::XML_PATH_EVENT_TRACKING_ENABLED,
                false
            );
        }
    }

    /**
     *  Check if event message queue is enabled & event should be sent via Message Queue
     *
     * @param int $storeId
     * @param string|null $eventName
     * @return bool
     */
    public function isEventMessageQueueEnabled(int $storeId, ?string $eventName = null): bool
    {
        if ($eventName) {
            return $this->dataStorage->getByScope(
                $storeId,
                self::XML_PATH_QUEUE_EVENTS . '/' .$eventName,
                false
            );
        } else {
            return $this->dataStorage->getByScope(
                $storeId,
                self::XML_PATH_QUEUE_ENABLED,
                false
            );
        }
    }
}
