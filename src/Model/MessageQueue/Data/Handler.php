<?php

namespace Synerise\Integration\Model\MessageQueue\Data;

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

    public function process(Message $update)
    {
        try {
            $this->execute($update);
        } catch (ApiException $e) {
        } catch (\Exception $e) {
            $this->logger->error('An error occurred while processing the queue message', ['exception' => $e]);
        }
    }

    /**
     * @param Message $update
     * @return void
     */
    private function execute(Message $update)
    {
        try {
            $executor = $this->synchronization->getExecutorByName($update->getModel());
            $items = $executor->getCollectionFilteredByEntityIds($update->getStoreId(), $update->getEntityId());
            $executor->sendItems($items, $update->getStoreId());
        } catch(ApiException $e) {
            if ($e->getCode() > 500) {
                $this->logger->debug('Publish for Retry: ' . $update->getModel() . ' id:'. $update->getEntityId() );
                $retries = $deserializedData['retries'] ?? 0;
                if ($retries < self::MAX_RETRIES) {
                    $retries++;
                    $this->queueHelper->publishUpdate($update->getModel(), $update->getStoreId(), $update->getEntityId(), $retries);
                }
            }
        }
    }
}
