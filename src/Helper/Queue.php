<?php
namespace Synerise\Integration\Helper;

use Magento\Store\Model\ScopeInterface;
use Synerise\Integration\Model\Synchronization\MessageQueue\Data\Single\Message;

class Queue extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_QUEUE_ENABLED = 'synerise/queue/enabled';
    const XML_PATH_QUEUE_EVENTS = 'synerise/queue/events';

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    private $publisher;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $json;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Magento\Framework\Serialize\Serializer\Json $json
    ) {
        parent::__construct($context);
        $this->publisher = $publisher;
        $this->json = $json;
    }

    public function isEventEnabled($event = null, $storeId = null): bool
    {
        if (!$event) {
            return true;
        }

        $events = explode(',', $this->scopeConfig->getValue(
            self::XML_PATH_QUEUE_EVENTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        return in_array($event, $events);
    }

    public function isQueueAvailable(string $event = null, int $storeId = null): bool
    {
        if (!$this->isQueueEnabled($storeId)) {
            return false;
        }

        return $this->isEventEnabled($event, $storeId);
    }

    private function isQueueEnabled(int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_QUEUE_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function publishEvent(string $eventName, $data, int $storeId, int $entityId = null, $retries = 0)
    {
        $serializedData = $this->json->serialize([
            'event_name' => $eventName,
            'event_payload' => $data,
            'store_id' => $storeId,
            'entity_id' => $entityId,
            'retries' => $retries
        ]);
        $this->publisher->publish('synerise.queue.events', $serializedData);
    }

    public function getEnabledStores()
    {
        $enabledStoresString = $this->scopeConfig->getValue(
            Synchronization::XML_PATH_SYNCHRONIZATION_STORES
        );

        return $enabledStoresString ? explode(',', $enabledStoresString) : [];
    }
}
