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
use Synerise\Integration\Model\Config\Source\MessageQueue\Connection;
use Zend_Db_Adapter_Exception;

abstract class AbstractOperationConsumer
{
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

    public function __construct(
        LoggerInterface $logger,
        ObjectManagerInterface $objectManager,
        EntityManager $entityManager,
        SerializerInterface $serializer,
        MessageEncoder $messageEncoder
    ) {
        $this->logger = $logger;
        $this->objectManager = $objectManager;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->messageEncoder = $messageEncoder;
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
            if ($data['retries'] <= Connection::MAX_RETRIES) {
                $isRetryScheduled = $this->scheduleRetry($operation);
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
     * @param OperationInterface $operation
     * @return bool
     */
    protected function scheduleRetry(OperationInterface $operation): bool
    {
        try {
            $topicName = self::getTopicName();

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

    /**
     * @param array $data
     * @return void
     * @throws ApiException
     * @throws Exception
     */
    abstract protected function execute(array $data);

    /**
     * @return string
     */
    abstract static protected function getModelName(): string;

    /**
     * @return string
     */
    abstract static protected function getTopicName(): string;
}