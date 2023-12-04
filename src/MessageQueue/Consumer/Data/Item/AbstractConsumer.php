<?php

namespace Synerise\Integration\MessageQueue\Consumer\Data\Item;

use Exception;
use GuzzleHttp\Exception\TransferException;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DB\Adapter\ConnectionException;
use Magento\Framework\DB\Adapter\DeadlockException;
use Magento\Framework\DB\Adapter\LockWaitException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\TemporaryStateExceptionInterface;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\ObjectManagerInterface;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\CatalogsApiClient\ApiException as CatalogApiException;
use Synerise\Integration\MessageQueue\Message\Data\Item;
use Synerise\Integration\MessageQueue\Publisher\Data\Item as Publisher;
use Synerise\Integration\Model\Config\Source\MessageQueue\Connection;
use Synerise\Integration\MessageQueue\Filter;
use Synerise\Integration\MessageQueue\Sender\Data\SenderInterface;
use Zend_Db_Adapter_Exception;

abstract class AbstractConsumer
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var MessageEncoder
     */
    protected $messageEncoder;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var SenderInterface
     */
    protected $sender;

    public function __construct(
        LoggerInterface $logger,
        ObjectManagerInterface $objectManager,
        MessageEncoder $messageEncoder,
        Filter $filter,
        SenderInterface $sender
    )
    {
        $this->logger = $logger;
        $this->objectManager = $objectManager;
        $this->messageEncoder = $messageEncoder;
        $this->filter = $filter;
        $this->sender = $sender;
    }

    /**
     * Process
     *
     * @param Item $item
     * @return void
     */
    public function process(Item $item)
    {
        $isRetryable = false;

        try {
            $this->execute($item);
        } catch(TransferException $e) {
            $this->logger->error($e->getMessage());
            $isRetryable = true;
        } catch(ApiException | CatalogApiException $e) {
            $isRetryable = ($e->getCode() == 0 || $e->getCode() == 401 || $e->getCode() > 500);
            $this->logger->critical($e->getMessage());
        } catch (Zend_Db_Adapter_Exception $e) {
            $this->logger->critical($e->getMessage());
            $isRetryable = ($e instanceof LockWaitException || $e instanceof DeadlockException || $e instanceof ConnectionException);
        } catch (NoSuchEntityException $e) {
            $this->logger->critical($e->getMessage());
            $isRetryable = ($e instanceof TemporaryStateExceptionInterface);
        } catch (LocalizedException|Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        if ($isRetryable) {
            $retries = $item->getRetries()+1;
            if ($retries <= Connection::MAX_RETRIES) {
                $item->setRetries($retries);
                $this->scheduleRetry($item);
            }
        }
    }
    
    /**
     * @param Item $item
     * @return void
     * @throws LocalizedException
     */
    protected function execute(Item $item)
    {
        $collection = $this->filter->filterByEntityId(
            $this->createCollection(),
            $item->getEntityId(),
            $item->getStoreId(),
            self::getPageSize()
        );

        $attributes = $this->sender->getAttributesToSelect($item->getStoreId());
        if(!empty($attributes)) {
            $collection->addAttributeToSelect($attributes);
        }

        $this->sender->sendItems($collection, $item->getStoreId());
    }

    /**
     * @param Item $item
     * @return bool
     */
    protected function scheduleRetry(Item $item): bool
    {
        try {
            $topicName = self::getTopicName();

            $retry = $this->objectManager->create('Synerise\Integration\Model\MessageQueue\Retry');
            $retry
                ->setBody($this->messageEncoder->encode($topicName, $item))
                ->setTopicName($topicName)
                ->save();

            return true;
        } catch(Exception $e) {
            $this->logger->error($e);
            return false;
        }
    }
    
    /**
     * @return string
     */
    static protected function getTopicName(): string
    {
        return Publisher::TOPIC_NAME;
    }

    /**
     * @return AbstractDb
     */
    abstract protected function createCollection(): AbstractDb;

    /**
     * @return string
     */
    abstract static protected function getModelName(): string;
    
    /**
     * @return int
     */
    abstract static protected function getPageSize(): int;
}
