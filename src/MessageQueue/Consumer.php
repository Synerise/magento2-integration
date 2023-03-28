<?php

namespace Synerise\Integration\MessageQueue;

use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Event;

class Consumer
{

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var Event
     */
    private $eventHelper;

    public function __construct(
        LoggerInterface $logger,
        Json $json,
        Event $eventHelper
    ) {
        $this->logger = $logger;
        $this->json = $json;
        $this->eventHelper = $eventHelper;
    }

    public function process(string $event)
    {
        try {
            $this->execute($event);
        } catch (ApiException $e) {
        } catch (\Exception $e) {
            $this->logger->error('An error occurred while processing the queue message', ['exception' => $e]);
        }
    }

    /**
     * @throws ApiException
     * @throws ValidatorException
     */
    private function execute(string $event)
    {
        $deserializedData = $this->json->unserialize($event);
        $eventName = $deserializedData['event_name'];
        $eventPayload = $deserializedData['event_payload'];
        $storeId = $deserializedData['store_id'];
        $entityId = $deserializedData['entity_id'];

        $this->eventHelper->sendEvent($eventName, $eventPayload, $storeId, $entityId);
    }
}
