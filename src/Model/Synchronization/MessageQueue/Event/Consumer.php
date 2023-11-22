<?php

namespace Synerise\Integration\Model\Synchronization\MessageQueue\Event;

use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Event;
use Synerise\Integration\Helper\Queue;

class Consumer
{
    const MAX_RETRIES = 3;

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

    /**
     * @var Queue
     */
    private $queueHelper;

    public function __construct(
        LoggerInterface $logger,
        Json $json,
        Event $eventHelper,
        Queue $queueHelper
    ) {
        $this->logger = $logger;
        $this->json = $json;
        $this->eventHelper = $eventHelper;
        $this->queueHelper = $queueHelper;
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

        try {
            $this->eventHelper->sendEvent($eventName, $eventPayload, $storeId, $entityId);
        } catch(ApiException $e) {
            if ($e->getCode() > 500) {
                $retries = $deserializedData['retries'] ?? 0;
                if ($retries < self::MAX_RETRIES) {
                    $retries++;
                    $this->queueHelper->publishEvent($eventName, $eventPayload, $storeId, $entityId, $retries);
                }
            }
        }
    }
}
