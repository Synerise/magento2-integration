<?php

namespace Synerise\Integration\Model\Tracking;

use Synerise\Integration\Model\Config\Source\CustomerDeleteBehavior;
use Synerise\Integration\Model\Tracking\Config\Data;
use Synerise\Integration\Model\Tracking\Config\DataFactory;

class Config
{
    public const XML_PATH_EVENT_TRACKING_ENABLED = 'synerise/event_tracking/enabled';

    public const XML_PATH_EVENT_TRACKING_EVENTS = 'synerise/event_tracking/events';

    public const XML_PATH_QUEUE_ENABLED = 'synerise/queue/enabled';

    public const XML_PATH_QUEUE_EVENTS = 'synerise/queue/events';

    public const XML_PATH_CUSTOMER_DELETE_BEHAVIOR = 'synerise/customer/delete_behavior';

    /**
     * @var Data
     */
    protected $dataStorage;

    /**
     * @var int
     */
    protected $storeId;

    /**
     * @param DataFactory $dataFactory
     * @param int $storeId
     */
    public function __construct(DataFactory $dataFactory, int $storeId)
    {
        $this->dataStorage = $dataFactory->create($storeId);
        $this->storeId = $storeId;
    }

    /**
     * Check if event tracking is enabled & event should be tracked
     *
     * @param string|null $eventName
     * @return bool
     */
    public function isEventTrackingEnabled(?string $eventName = null): bool
    {
        if ($eventName) {
            return in_array($eventName, $this->getEventsEnabledForTracking());
        } else {
            return $this->dataStorage->get('enabled', false);
        }
    }

    /**
     * Get an array of events enabled for tracking
     *
     * @return array
     */
    public function getEventsEnabledForTracking(): array
    {
        return $this->dataStorage->get('events', []);
    }

    /**
     *  Check if event message queue is enabled & event should be sent via Message Queue
     *
     * @param string|null $eventName
     * @return bool
     */
    public function isEventMessageQueueEnabled(?string $eventName = null): bool
    {
        if ($eventName) {
            return in_array($eventName, $this->getEventsEnabledForQueue());
        } else {
            return $this->dataStorage->get('queue_enabled', false);
        }
    }

    /**
     * Get an array of events enabled for message queue
     *
     * @return array
     */
    public function getEventsEnabledForQueue(): array
    {
        return $this->dataStorage->get('queue_events', []);
    }

    /**
     * Get customer delete behavior
     *
     * @return string
     */
    public function getCustomerDeleteBehavior(): string
    {
        return $this->dataStorage->get('customer_delete_behavior', CustomerDeleteBehavior::SEND_EVENT);
    }
}
