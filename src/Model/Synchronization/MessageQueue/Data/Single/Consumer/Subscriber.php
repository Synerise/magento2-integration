<?php

namespace Synerise\Integration\Model\Synchronization\MessageQueue\Data\Single\Consumer;

use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Model\Synchronization\MessageQueue\Data\Single\ConsumerInterface;
use Synerise\Integration\Model\Synchronization\MessageQueue\Data\Single\Message;
use Synerise\Integration\Model\Synchronization\Sender\Subscriber as Sender;
use Synerise\Integration\Model\Synchronization\Provider\Subscriber as Provider;

class Subscriber implements ConsumerInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Provider
     */
    private $provider;

    /**
     * @var Sender
     */
    private $sender;

    public function __construct(
        LoggerInterface $logger,
        Provider $provider,
        Sender $sender
    ) {
        $this->logger = $logger;
        $this->provider = $provider;
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

    private function execute(Message $message)
    {
        /** @var Collection $collection */
        $collection = $this->provider->createCollection()
            ->addStoreFilter($message->getStoreId())
            ->filterByEntityId($message->getEntityId())
            ->getCollection();

        $this->sender->sendItems($collection, $message->getStoreId());
    }
}