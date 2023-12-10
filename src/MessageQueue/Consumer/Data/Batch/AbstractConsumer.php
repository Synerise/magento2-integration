<?php

namespace Synerise\Integration\MessageQueue\Consumer\Data\Batch;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\MessageQueue\Consumer\Data\AbstractOperationConsumer;
use Synerise\Integration\MessageQueue\Filter;
use Synerise\Integration\SyneriseApi\Sender\Data\SenderInterface;

abstract class AbstractConsumer extends AbstractOperationConsumer
{

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var SenderInterface
     */
    private $sender;

    public function __construct(
        LoggerInterface $logger,
        ObjectManagerInterface $objectManager,
        EntityManager $entityManager,
        SerializerInterface $serializer,
        MessageEncoder $messageEncoder,
        Filter $filter,
        SenderInterface $sender
    ) {
        $this->filter = $filter;
        $this->sender = $sender;

        parent::__construct($logger, $objectManager, $entityManager, $serializer, $messageEncoder);
    }

    /**
     * @param array $data
     * @return void
     * @throws ApiException
     * @throws LocalizedException
     */
    protected function execute(array $data)
    {
        $collection = $this->filter->filterByEntityIds(
            $this->createCollection(),
            $data['entity_ids'],
            $data['store_id'],
            static::getPageSize()
        );

        $attributes = $this->sender->getAttributesToSelect($data['store_id']);
        if(!empty($attributes)) {
            $collection->addAttributeToSelect($attributes);
        }

        $this->sender->sendItems(
            $collection,
            $data['store_id'],
            $data['website_id'] ?: null
        );    }

    /**
     * @return AbstractDb
     */
    abstract protected function createCollection(): AbstractDb;

    /**
     * @return int
     */
    abstract static protected function getPageSize(): int;
}
