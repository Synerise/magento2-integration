<?php

namespace Synerise\Integration\Cron\MessageQueue;

use Psr\Log\LoggerInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Synerise\Integration\Model\ResourceModel\MessageQueue\Retry\CollectionFactory;

class Retry
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var \Magento\Framework\MessageQueue\MessageEncoder
     */
    private $messageEncoder;

    public function __construct(
        LoggerInterface $logger,
        PublisherInterface $publisher,
        CollectionFactory $collectionFactory
    ){
        $this->logger = $logger;
        $this->publisher = $publisher;
        $this->collectionFactory = $collectionFactory;

        $this->messageEncoder = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Framework\MessageQueue\MessageEncoder');

    }

    public function execute()
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('created_at', ['lt' => 'NOW() - INTERVAL 5 MINUTE'])
            ->setPageSize(500);

        foreach ($collection as $item) {
            try {
                $this->publisher->publish(
                    $item->getTopicName(),
                    $this->messageEncoder->decode($item->getTopicName(), $item->getBody())
                );

                $item->delete();
            } catch(\Exception $e) {
                $this->logger->error($e);
            }
        }
    }
}