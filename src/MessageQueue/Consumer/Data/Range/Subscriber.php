<?php

namespace Synerise\Integration\MessageQueue\Consumer\Data\Range;

use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;
use Synerise\Integration\MessageQueue\Filter;
use Synerise\Integration\MessageQueue\Publisher\Data\Range;
use Synerise\Integration\SyneriseApi\Sender\Data\Subscriber as Sender;

class Subscriber extends AbstractConsumer
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
        return Range::getTopicName(Sender::MODEL);
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
