<?php

namespace Synerise\Integration\Model\Queue\Update;

use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Cron\Synchronization;
use Synerise\Integration\Helper\Event;
use Synerise\Integration\Helper\Queue;

class Handler
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

    /**
     * @var Synchronization
     */
    private $synchronization;

    public function __construct(
        LoggerInterface $logger,
        Json $json,
        Synchronization $synchronization,
        Event $eventHelper,
        Queue $queueHelper
    ) {
        $this->logger = $logger;
        $this->synchronization = $synchronization;
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
     * @param string $update
     * @return void
     */
    private function execute(string $update)
    {
        try {
            $deserializedData = $this->json->unserialize($update);

            $executor = $this->synchronization->getExecutorByName($deserializedData['model']);
            $items = $executor->getCollectionFilteredByEntityIds(
                $deserializedData['store_id'],
                [$deserializedData['entity_id']]
            );

            $executor->sendItems($items, $deserializedData['store_id']);
        } catch(ApiException $e) {
            if ($e->getCode() > 500) {
                $this->logger->debug('Publish for Retry: ' . $deserializedData['model'] . ' id:'. $deserializedData['entity_id'] );
                $retries = $deserializedData['retries'] ?? 0;
                if ($retries < self::MAX_RETRIES) {
                    $retries++;
                    $this->queueHelper->publishUpdate(
                        $deserializedData['model'],
                        $deserializedData['store_id'],
                        $deserializedData['entity_id'],
                        $retries
                    );
                }
            }
        }
    }
}
