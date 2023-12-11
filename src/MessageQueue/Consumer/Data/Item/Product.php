<?php

namespace Synerise\Integration\MessageQueue\Consumer\Data\Item;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\ObjectManagerInterface;
use Psr\Log\LoggerInterface;
use Synerise\Integration\MessageQueue\Filter;
use Synerise\Integration\SyneriseApi\Sender\Data\Product as Sender;

class Product extends AbstractConsumer implements ConsumerInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    public function __construct(
        LoggerInterface $logger,
        ObjectManagerInterface $objectManager,
        MessageEncoder $messageEncoder,
        CollectionFactory $collectionFactory,
        Filter $filter,
        Sender $sender
    ) {
        $this->collectionFactory = $collectionFactory;

        parent::__construct(
            $logger,
            $objectManager,
            $messageEncoder,
            $filter,
            $sender
        );
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
