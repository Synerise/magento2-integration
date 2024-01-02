<?php

namespace Synerise\Integration\MessageQueue\Consumer\Data;

use Exception;
use Magento\AsynchronousOperations\Api\Data\OperationInterface as AsynchronousOperationInterface;
use Magento\Framework\Bulk\OperationInterface as BulkOperationInterface;
use Magento\Framework\DB\Adapter\ConnectionException;
use Magento\Framework\DB\Adapter\DeadlockException;
use Magento\Framework\DB\Adapter\LockWaitException;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\TemporaryStateExceptionInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Customer\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection as SubscriberCollection;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\MessageQueue\CollectionFactoryProvider;
use Synerise\Integration\MessageQueue\Publisher\Data\All as Publisher;
use Synerise\Integration\MessageQueue\Filter;
use Synerise\Integration\MessageQueue\Publisher\Data\AbstractBulk;
use Synerise\Integration\SyneriseApi\Sender\Data\Product;
use Zend_Db_Adapter_Exception;

abstract class AbstractScheduler
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var CollectionFactoryProvider
     */
    protected $collectionFactoryProvider;
    
    /**
     * @var Filter
     */
    protected $filter;
    
    /**
     * @var Publisher
     */
    protected $publisher;

    /**
     * @var Synchronization
     */
    protected $synchronization;

    /**
     * @param LoggerInterface $logger
     * @param SerializerInterface $serializer
     * @param EntityManager $entityManager
     * @param CollectionFactoryProvider $collectionFactoryProvider
     * @param Filter $filter
     * @param Publisher $publisher
     * @param Synchronization $synchronization
     */
    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        EntityManager $entityManager,
        CollectionFactoryProvider $collectionFactoryProvider,
        Filter $filter,
        Publisher $publisher,
        Synchronization $synchronization
    ) {
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->collectionFactoryProvider = $collectionFactoryProvider;
        $this->filter = $filter;
        $this->publisher = $publisher;
        $this->synchronization = $synchronization;
    }

    /**
     * Process
     *
     * @param AsynchronousOperationInterface $operation
     * @return void
     * @throws Exception
     */
    public function process(AsynchronousOperationInterface $operation)
    {
        try {
            $data = $this->serializer->unserialize($operation->getSerializedData());
            $this->purgeQueue(AbstractBulk::getTopicName(
                $data['model'],
                Publisher::TYPE,
                $data['store_id']
            ));
            $this->execute($data);
        } catch (Zend_Db_Adapter_Exception $e) {
            $this->logger->critical($e->getMessage());
            if ($e instanceof LockWaitException ||
                $e instanceof DeadlockException ||
                $e instanceof ConnectionException) {
                $status = BulkOperationInterface::STATUS_TYPE_RETRIABLY_FAILED;
                $errorCode = $e->getCode();
                $message = $e->getMessage();
            } else {
                $status = BulkOperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
                $errorCode = $e->getCode();
                $message = __(
                    'Sorry, something went wrong. Please see log for details.'
                );
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->critical($e->getMessage());
            $status = ($e instanceof TemporaryStateExceptionInterface)
                ? BulkOperationInterface::STATUS_TYPE_RETRIABLY_FAILED
                : BulkOperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = $e->getMessage();
        } catch (LocalizedException $e) {
            $this->logger->critical($e->getMessage());
            $status = BulkOperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = $e->getMessage();
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());
            $status = BulkOperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = __('Sorry, something went wrong. Please see log for details.');
        }

        $operation->setStatus($status ?? BulkOperationInterface::STATUS_TYPE_COMPLETE)
            ->setErrorCode($errorCode ?? null)
            ->setResultMessage($message ?? null);

        $this->entityManager->save($operation);
    }

    /**
     * Schedule full synchronization by message data
     *
     * @param array $data
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws Exception
     */
    protected function execute(array $data)
    {
        $collectionFactory = $this->collectionFactoryProvider->get($data['model']);
        /** @var CustomerCollection|OrderCollection|ProductCollection|SubscriberCollection $collection */
        $collection = $this->filter->addStoreFilter($collectionFactory->create(), $data['store_id']);

        $pageSize = $this->synchronization->getPageSize($data['model'], $data['store_id']);

        $gt = 0;
        $lastId = $this->filter->getLastId($collection);
        if (!$lastId) {
            return;
        }

        $ids = [];
        while ($gt < $lastId) {
            $curIds = $collection->getAllIds($pageSize, $gt);
            if (!count($curIds)) {
                break;
            }

            $ids[] = $curIds;
            $gt = (int) end($curIds);
        }

        if (!empty($ids)) {
            $this->publisher->schedule(
                $data['user_id'],
                $data['model'],
                $ids,
                $data['store_id'],
                $data['model'] == Product::MODEL ?
                    $this->synchronization->getWebsiteIdByStoreId($data['store_id']) : null
            );
        }
    }

    /**
     * Purge messages from queue
     *
     * @param string $topicName
     * @return void
     */
    abstract protected function purgeQueue(string $topicName);
}
