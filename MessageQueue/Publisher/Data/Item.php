<?php

namespace Synerise\Integration\MessageQueue\Publisher\Data;

use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Synerise\Integration\MessageQueue\Message\Data\Item as MessageItem;

class Item
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
    public function __construct(PublisherInterface $publisher, Json $json)
    {
        $this->publisher = $publisher;
        $this->json = $json;
    }

    public const TOPIC_NAME = 'synerise.queue.data.item';

    /**
     * Publish message to queue
     *
     * @param string $model
     * @param int $entityId
     * @param int $storeId
     * @param array $options
     * @param int|null $websiteId
     * @param int|null $retries
     * @return void
     */
    public function publish(
        string $model,
        int $entityId,
        int $storeId,
        array $options = [],
        ?int $websiteId = null,
        ?int $retries = 0
    ) {
        $options['type'] = 'item';

        $this->publisher->publish(
            self::TOPIC_NAME,
            new MessageItem($model, $entityId, $storeId, $websiteId, $retries, $this->json->serialize($options))
        );
    }
}
