<?php

namespace Synerise\Integration\MessageQueue\Consumer\Data;

use Exception;
use GuzzleHttp\Exception\TransferException;
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Framework\Bulk\OperationInterface as BulkOperationInterface;
use Magento\Framework\DB\Adapter\ConnectionException;
use Magento\Framework\DB\Adapter\DeadlockException;
use Magento\Framework\DB\Adapter\LockWaitException;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\TemporaryStateExceptionInterface;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\CatalogsApiClient\ApiException as CatalogApiException;
use Synerise\Integration\Communication\Config;
use Synerise\Integration\MessageQueue\CollectionFactoryProvider;
use Synerise\Integration\MessageQueue\Filter;
use Synerise\Integration\MessageQueue\Publisher\Data\AbstractBulk as BulkPublisher;
use Synerise\Integration\SyneriseApi\SenderFactory;
use Zend_Db_Adapter_Exception;

class Bulk
{
    const MAX_PAGE_SIZE = 100;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var MessageEncoder
     */
    private $messageEncoder;

    /**
     * @var CollectionFactoryProvider
     */
    private $collectionFactoryProvider;

    /**
     * @var SenderFactory
     */
    private $senderFactory;

    /**
     * @var Filter
     */
    private $filter;

    public function __construct(
        LoggerInterface $logger,
        ObjectManagerInterface $objectManager,
        EntityManager $entityManager,
        SerializerInterface $serializer,
        MessageEncoder $messageEncoder,
        CollectionFactoryProvider $collectionFactoryProvider,
        SenderFactory $senderFactory,
        Filter $filter
    ) {
        $this->logger = $logger;
        $this->objectManager = $objectManager;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->messageEncoder = $messageEncoder;
        $this->collectionFactoryProvider = $collectionFactoryProvider;
        $this->senderFactory = $senderFactory;
        $this->filter = $filter;
    }

    /**
     * @param array $data
     * @return void
     * @throws ApiException
     * @throws LocalizedException
     */
    protected function execute(array $data)
    {
        $sender = $this->senderFactory->get($data['model']);

        $collection = $this->filter->filterByEntityIds(
            $this->collectionFactoryProvider->get($data['model'])->create(),
            $data['entity_ids'],
            $data['store_id'],
            self::MAX_PAGE_SIZE
        );

        $attributes = $sender->getAttributesToSelect($data['store_id']);
        if(!empty($attributes)) {
            $collection->addAttributeToSelect($attributes);
        }

        $sender->sendItems(
            $collection,
            $data['store_id'],
            $data['website_id'] ?: null
        );
    }

    /**
     * Process
     *
     * @param OperationInterface $operation
     * @throws Exception
     *
     * @return void
     */
    public function process(OperationInterface $operation)
    {
        $status = null;
        $data = $this->serializer->unserialize($operation->getSerializedData());

        try {
            $this->execute($data);
        } catch(TransferException $e) {
            $this->logger->error($e->getMessage());
            $message = $e->getMessage();
            $errorCode = $e->getCode();
            $status = BulkOperationInterface::STATUS_TYPE_RETRIABLY_FAILED;
        } catch(ApiException | CatalogApiException $e) {
            $message = $e->getMessage();
            $errorCode = $e->getCode();
            if ($e->getCode() == 0 || $e->getCode() == 401 || $e->getCode() > 500) {
                $this->logger->error($e->getMessage());
                $status = BulkOperationInterface::STATUS_TYPE_RETRIABLY_FAILED;
            } else {
                $this->logger->critical($e->getMessage());
                $status = BulkOperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            }
        } catch (Zend_Db_Adapter_Exception $e) {
            $this->logger->critical($e->getMessage());
            if ($e instanceof LockWaitException || $e instanceof DeadlockException || $e instanceof ConnectionException) {
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

        $isRetryScheduled = false;
        if ($status == BulkOperationInterface::STATUS_TYPE_RETRIABLY_FAILED) {
            $data['retries'] = (isset($data['retries'])) ? ++$data['retries'] : 0;
            if ($data['retries'] <= Config::MAX_RETRIES) {
                $isRetryScheduled = $this->scheduleRetry(
                    BulkPublisher::getTopicName($data['model'], $data['type'], $data['store_id']),
                    $operation
                );
            }
        }

        if (!$isRetryScheduled) {
            $operation->setStatus($status ?? BulkOperationInterface::STATUS_TYPE_COMPLETE)
                ->setSerializedData($this->serializer->serialize($data))
                ->setErrorCode($errorCode ?? null)
                ->setResultMessage($message ?? null);

            $this->entityManager->save($operation);
        }
    }

    /**
     * @param string $topicName
     * @param OperationInterface $operation
     * @return bool
     */
    protected function scheduleRetry(
        string $topicName,
        OperationInterface $operation
    ): bool
    {
        try {
            $retry = $this->objectManager->create('Synerise\Integration\Model\MessageQueue\Retry');
            $retry
                ->setBody($this->messageEncoder->encode($topicName, $operation))
                ->setTopicName($topicName)
                ->save();

            return true;
        } catch(Exception $e) {
            $this->logger->error($e);
            return false;
        }
    }
}
