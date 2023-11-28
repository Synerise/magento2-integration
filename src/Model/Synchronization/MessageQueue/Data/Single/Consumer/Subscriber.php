<?php

namespace Synerise\Integration\Model\Synchronization\MessageQueue\Data\Single\Consumer;

use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Model\Synchronization\Filter;
use Synerise\Integration\Model\Synchronization\MessageQueue\Data\Single\ConsumerInterface;
use Synerise\Integration\Model\Synchronization\MessageQueue\Data\Single\Message;
use Synerise\Integration\Model\Synchronization\Sender\Subscriber as Sender;

class Subscriber implements ConsumerInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var Sender
     */
    private $sender;

    public function __construct(
        LoggerInterface $logger,
        CollectionFactory $collectionFactory,
        Filter $filter,
        Sender $sender
    ) {
        $this->logger = $logger;
        $this->collectionFactory = $collectionFactory;
        $this->filter = $filter;
        $this->sender = $sender;
    }

    public function process(Message $message)
    {
        try {
            $this->execute($message);
        } catch (ApiException $e) {
        } catch (\Exception $e) {
            $this->logger->error('An error occurred while processing the queue message', ['exception' => $e]);
        }
    }

    /**
     * @param Message $message
     * @return void
     * @throws ApiException
     */
    private function execute(Message $message)
    {
        /** @var Collection $collection */
        $collection = $this->filter->filterByEntityId(
            $this->collectionFactory->create(),
            $message->getEntityId(),
            $message->getStoreId(),
            Sender::MAX_PAGE_SIZE
        );

        $this->sender->sendItems($collection, $message->getStoreId());
    }
}