<?php

namespace Synerise\Integration\Cron;

use Magento\Framework\MessageQueue\MessageEncoder;
use Psr\Log\LoggerInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Synerise\Integration\Model\ResourceModel\MessageQueue\Retry\CollectionFactory;

class MessageQueueRetry
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
     * @var MessageEncoder
     */
    private $messageEncoder;

    /**
     * @param LoggerInterface $logger
     * @param PublisherInterface $publisher
     * @param CollectionFactory $collectionFactory
     * @param MessageEncoder $messageEncoder
     */
    public function __construct(
        LoggerInterface $logger,
        PublisherInterface $publisher,
        CollectionFactory $collectionFactory,
        MessageEncoder $messageEncoder
    ) {
        $this->logger = $logger;
        $this->publisher = $publisher;
        $this->collectionFactory = $collectionFactory;
        $this->messageEncoder = $messageEncoder;
    }

    /**
     * Add applicable messages back to the queues.
     *
     * @return void
     */
    public function execute()
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('created_at', ['lt' => new \Zend_Db_Expr('NOW() - INTERVAL 5 MINUTE')])
            ->setPageSize(500);

        if ($collection->getSize()) {
            foreach ($collection as $item) {
                try {
                    $this->publisher->publish(
                        $item->getTopicName(),
                        $this->messageEncoder->decode($item->getTopicName(), $item->getBody())
                    );
                } catch (\Exception $e) {
                    $this->logger->error($e);
                }

                $item->delete();
            }
        }
    }
}
