<?php

namespace Synerise\Integration\MessageQueue\Consumer\Data;

use Magento\Framework\Amqp\Config as AmqpConfig;
use Magento\Framework\Bulk\OperationInterface;
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
use Synerise\Integration\MessageQueue\Publisher\Data\Range as Publisher;
use Synerise\Integration\MessageQueue\Filter;
use Synerise\Integration\SyneriseApi\Sender\Data\Product;

class Scheduler
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var AmqpConfig
     */
    private $amqpConfig;

    /**
     * @var CollectionFactoryProvider
     */
    private $collectionFactoryProvider;
    
    /**
     * @var Filter
     */
    private $filter;
    
    /**
     * @var Publisher
     */
    private $publisher;

    /**
     * @var Synchronization
     */
    private $synchronization;

    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        EntityManager $entityManager,
        AmqpConfig $amqpConfig,
        CollectionFactoryProvider $collectionFactoryProvider,
        Filter $filter,
        Publisher $publisher,
        Synchronization $synchronization
    ) {
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->amqpConfig = $amqpConfig;
        $this->collectionFactoryProvider = $collectionFactoryProvider;
        $this->filter = $filter;
        $this->publisher = $publisher;
        $this->synchronization = $synchronization;
    }

    /**
     * Process
     *
     * @param \Magento\AsynchronousOperations\Api\Data\OperationInterface $operation
     * @throws \Exception
     *
     * @return void
     */
    public function process(\Magento\AsynchronousOperations\Api\Data\OperationInterface $operation)
    {
        try {
            $this->execute($this->serializer->unserialize($operation->getSerializedData()));
        } catch (\Zend_Db_Adapter_Exception $e) {
            $this->logger->critical($e->getMessage());
            if ($e instanceof \Magento\Framework\DB\Adapter\LockWaitException
                || $e instanceof \Magento\Framework\DB\Adapter\DeadlockException
                || $e instanceof \Magento\Framework\DB\Adapter\ConnectionException
            ) {
                $status = OperationInterface::STATUS_TYPE_RETRIABLY_FAILED;
                $errorCode = $e->getCode();
                $message = $e->getMessage();
            } else {
                $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
                $errorCode = $e->getCode();
                $message = __(
                    'Sorry, something went wrong. Please see log for details.'
                );
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->critical($e->getMessage());
            $status = ($e instanceof TemporaryStateExceptionInterface)
                ? OperationInterface::STATUS_TYPE_RETRIABLY_FAILED
                : OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = $e->getMessage();
        } catch (LocalizedException $e) {
            $this->logger->critical($e->getMessage());
            $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = $e->getMessage();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = __('Sorry, something went wrong. Please see log for details.');
        }

        $operation->setStatus($status ?? OperationInterface::STATUS_TYPE_COMPLETE)
            ->setErrorCode($errorCode ?? null)
            ->setResultMessage($message ?? null);

        $this->entityManager->save($operation);
    }

    /**
     * @param array $data
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws \Exception
     */
    protected function execute(array $data)
    {
        $this->amqpConfig->getChannel()->queue_purge(Publisher::getTopicName($data['model']));

        $collectionFactory = $this->collectionFactoryProvider->get($data['model']);
        /** @var CustomerCollection|OrderCollection|ProductCollection|SubscriberCollection $collection */
        $collection = $this->filter->addStoreFilter($collectionFactory->create(), $data['store_id']);
        
        $pageSize = $this->synchronization->getPageSize($data['model'], $data['store_id']);

        $le = $gt = 0;
        $lastId = $this->filter->getLastId($collection);
        if (!$lastId) {
            return;
        }

        $ranges = [];
        while ($le < $lastId) {
            $ids = $collection->getAllIds($pageSize, $gt);
            if (!count($ids)) {
                break;
            }

            $le = (int) end($ids);
            $ranges[] = [
                'gt' => $gt,
                'le' => $le
            ];

            $gt = $le;
        }

        $this->publisher->schedule(
            $data['user_id'],
            $data['model'],
            $ranges,
            $data['store_id'],
            $data['model'] == Product::MODEL ? $this->synchronization->getWebsiteIdByStoreId($data['store_id']): null
        );
    }
}
