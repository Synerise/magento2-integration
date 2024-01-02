<?php

namespace Synerise\Integration\MessageQueue\Consumer\Data;

use Magento\Framework\Amqp\Config as AmqpConfig;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\MessageQueue\CollectionFactoryProvider;
use Synerise\Integration\MessageQueue\Publisher\Data\All as Publisher;
use Synerise\Integration\MessageQueue\Filter;

class AmqpScheduler extends AbstractScheduler
{
    /**
     * @var AmqpConfig
     */
    protected $amqpConfig;

    /**
     * @param LoggerInterface $logger
     * @param SerializerInterface $serializer
     * @param EntityManager $entityManager
     * @param CollectionFactoryProvider $collectionFactoryProvider
     * @param Filter $filter
     * @param Publisher $publisher
     * @param Synchronization $synchronization
     * @param AmqpConfig $amqpConfig
     */
    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        EntityManager $entityManager,
        CollectionFactoryProvider $collectionFactoryProvider,
        Filter $filter,
        Publisher $publisher,
        Synchronization $synchronization,
        AmqpConfig $amqpConfig
    ) {

        $this->amqpConfig = $amqpConfig;

        parent::__construct(
            $logger,
            $serializer,
            $entityManager,
            $collectionFactoryProvider,
            $filter,
            $publisher,
            $synchronization
        );
    }

    /**
     * @inheritDoc
     */
    protected function purgeQueue(string $topicName)
    {
        $this->amqpConfig->getChannel()->queue_purge($topicName);
    }
}
