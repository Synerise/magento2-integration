<?php

namespace Synerise\Integration\Model\Synchronization\MessageQueue\Data\Range\Consumer;

use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory;
use Magento\Framework\Bulk\OperationInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\TemporaryStateExceptionInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Model\Synchronization\Filter;
use Synerise\Integration\Model\Synchronization\Sender\Subscriber as Sender;

class Subscriber
{
    const MAX_PAGE_SIZE = 500;

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
        SerializerInterface $serializer,
        EntityManager $entityManager,
        CollectionFactory $collectionFactory,
        Filter $filter,
        Sender $sender
    ) {
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->collectionFactory = $collectionFactory;
        $this->filter = $filter;
        $this->sender = $sender;
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
        } catch(ApiException $e) {
            $message = $e->getMessage();
            if ($e->getCode() == 0 || $e->getCode() == 401 || $e->getCode() > 500) {
                $status = OperationInterface::STATUS_TYPE_RETRIABLY_FAILED;
            } else {
                $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            }
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
     * @throws ApiException
     * @throws \Exception
     */
    private function execute(array $data)
    {
        /** @var Collection $collection */
        $collection = $this->filter->filterByEntityRange(
            $this->collectionFactory->create(),
            $data['gt'],
            $data['le'],
            $data['store_id'],
            Sender::MAX_PAGE_SIZE
        );

        $this->sender->sendItems($collection, $data['store_id']);
    }
}