<?php

namespace Synerise\Integration\Model\Synchronization\MessageQueue\Data\Single\Consumer;

use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Model\Synchronization\MessageQueue\Data\Single\ConsumerInterface;
use Synerise\Integration\Model\Synchronization\MessageQueue\Data\Single\Message;
use Synerise\Integration\Model\Synchronization\Sender\Product as Sender;
use Synerise\Integration\Model\Synchronization\Provider\Product as Provider;

class Product implements ConsumerInterface
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

    /**
     * @param Message $message
     * @return void
     */
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
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \Synerise\CatalogsApiClient\ApiException
     */
    private function execute(Message $message)
    {
        $collection = $this->provider->createCollection()
            ->addStoreFilter($message->getStoreId())
            ->addAttributesToSelect($message->getStoreId())
            ->filterByEntityId($message->getEntityId())
            ->getCollection();

        $this->sender->sendItems($collection, $message->getStoreId());
    }
}