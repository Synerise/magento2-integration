<?php

namespace Synerise\Integration\MessageQueue\Consumer\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\MessageQueue\CollectionFactoryProvider;
use Synerise\Integration\MessageQueue\Publisher\Data\All as Publisher;
use Synerise\Integration\MessageQueue\Filter;

class MysqlScheduler extends AbstractScheduler
{
    /**
     * @var AdapterInterface
     */
    protected $connection;

    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        EntityManager $entityManager,
        CollectionFactoryProvider $collectionFactoryProvider,
        Filter $filter,
        Publisher $publisher,
        Synchronization $synchronization,
        ResourceConnection $resource
    ) {
        $this->connection = $resource->getConnection();

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

    protected function purgeQueue(string $topicName)
    {
    }
}
