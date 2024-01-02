<?php

namespace Synerise\Integration\MessageQueue\Consumer\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\MysqlMq\Model\QueueManagement;
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

    /**
     * @param LoggerInterface $logger
     * @param SerializerInterface $serializer
     * @param EntityManager $entityManager
     * @param CollectionFactoryProvider $collectionFactoryProvider
     * @param Filter $filter
     * @param Publisher $publisher
     * @param Synchronization $synchronization
     * @param ResourceConnection $resource
     */
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

    /**
     * @inheritDoc
     */
    protected function purgeQueue(string $topicName)
    {
        $messageStatusTable = $this->connection->getTableName('queue_message_status');
        $messageTable = $this->connection->getTableName('queue_message');

        $query = sprintf(
            "UPDATE %s JOIN %s ON %s.message_id = %s.id SET status = %s
                WHERE queue_message_status.status IN (%s) AND topic_name = '%s'",
            $messageStatusTable,
            $messageTable,
            $messageStatusTable,
            $messageTable,
            QueueManagement::MESSAGE_STATUS_TO_BE_DELETED,
            implode(",", [QueueManagement::MESSAGE_STATUS_NEW, QueueManagement::MESSAGE_STATUS_RETRY_REQUIRED]),
            $topicName
        );

        return $this->connection->query($query);
    }
}
