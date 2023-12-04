<?php

namespace Synerise\Integration\MessageQueue\Consumer\Data\Batch;

use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;
use Synerise\Integration\MessageQueue\Filter;
use Synerise\Integration\MessageQueue\Publisher\Data\Batch;
use Synerise\Integration\MessageQueue\Sender\Data\Order as Sender;

class Order extends AbstractConsumer
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    public function __construct(
        LoggerInterface $logger,
        ObjectManagerInterface $objectManager,
        EntityManager $entityManager,
        SerializerInterface $serializer,
        MessageEncoder $messageEncoder,
        CollectionFactory $collectionFactory,
        Filter $filter,
        Sender $sender
    ) {
        $this->collectionFactory = $collectionFactory;

        parent::__construct(
            $logger,
            $objectManager,
            $entityManager,
            $serializer,
            $messageEncoder,
            $filter,
            $sender
        );
    }

    /**
     * @inheritDoc
     */
    static protected function getTopicName(): string
    {
        return Batch::getTopicName(Sender::MODEL);
    }

    /**
     * @return Collection
     */
    protected function createCollection(): AbstractDb
    {
        return $this->collectionFactory->create();
    }

    /**
     * @inheritDoc
     */
    static protected function getModelName(): string
    {
        return Sender::MODEL;
    }

    /**
     * @inheritDoc
     */
    static protected function getPageSize(): int
    {
        return Sender::MAX_PAGE_SIZE;
    }
}
