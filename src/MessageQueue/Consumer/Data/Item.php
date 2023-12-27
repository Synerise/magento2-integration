<?php

namespace Synerise\Integration\MessageQueue\Consumer\Data;

use Exception;
use Synerise\Integration\Communication\Config;
use Zend_Db_Adapter_Exception;
use GuzzleHttp\Exception\TransferException;
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
use Synerise\Integration\MessageQueue\CollectionFactoryProvider;
use Synerise\Integration\MessageQueue\Filter;
use Synerise\Integration\MessageQueue\Message\Data\Item as ItemMessage;
use Synerise\Integration\SyneriseApi\SenderFactory;
use Synerise\Integration\MessageQueue\Publisher\Data\Item as Publisher;

class Item
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
     * @var CollectionFactoryProvider
     */
    protected $collectionFactoryProvider;

    /**
     * @var SenderFactory
     */
    protected $senderFactory;

    /**
     * @var Filter
     */
    protected $filter;

    public function __construct(
        LoggerInterface $logger,
        ObjectManagerInterface $objectManager,
        MessageEncoder $messageEncoder,
        CollectionFactoryProvider $collectionFactoryProvider,
        SenderFactory $senderFactory,
        Filter $filter
    ) {
        $this->logger = $logger;
        $this->objectManager = $objectManager;
        $this->messageEncoder = $messageEncoder;
        $this->collectionFactoryProvider = $collectionFactoryProvider;
        $this->senderFactory = $senderFactory;
        $this->filter = $filter;
    }

    /**
     * Process
     *
     * @param ItemMessage $item
     * @return void
     */
    public function process(ItemMessage $item)
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
            if ($retries <= Config::MAX_RETRIES) {
                $item->setRetries($retries);
                $this->scheduleRetry($item);
            }
        }
    }

    /**
     * @param ItemMessage $item
     * @return void
     * @throws ApiException
     * @throws CatalogApiException
     * @throws LocalizedException
     */
    protected function execute(ItemMessage $item)
    {
        $sender = $this->senderFactory->get($item->getModel());

        $collection = $this->filter->filterByEntityId(
            $this->collectionFactoryProvider->get($item->getModel())->create(),
            $item->getEntityId(),
            $item->getStoreId(),
            1
        );

        $attributes = $sender->getAttributesToSelect($item->getStoreId());
        if(!empty($attributes)) {
            $collection->addAttributeToSelect($attributes);
        }

        $sender->sendItems($collection, $item->getStoreId(), $item->getWebsiteId());
    }

    /**
     * @param ItemMessage $item
     * @return bool
     */
    protected function scheduleRetry(ItemMessage $item): bool
    {
        try {
            $topicName = Publisher::TOPIC_NAME;

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
}
