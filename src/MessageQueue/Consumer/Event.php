<?php

namespace Synerise\Integration\MessageQueue\Consumer;

use Exception;
use GuzzleHttp\Exception\TransferException;
use Magento\Framework\DB\Adapter\ConnectionException;
use Magento\Framework\DB\Adapter\DeadlockException;
use Magento\Framework\DB\Adapter\LockWaitException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\TemporaryStateExceptionInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\CatalogsApiClient\ApiException as CatalogApiException;
use Synerise\Integration\MessageQueue\Publisher\Data\Item as DataItemPublisher;
use Synerise\Integration\MessageQueue\Sender\Data\Customer;
use Synerise\Integration\MessageQueue\Sender\Data\Order;
use Synerise\Integration\MessageQueue\Sender\Data\Subscriber;
use Synerise\Integration\MessageQueue\Sender\Event as Sender;
use Synerise\Integration\Model\Config\Source\MessageQueue\Connection;
use Synerise\Integration\Observer\NewsletterSubscriberDeleteAfter;
use Synerise\Integration\Observer\NewsletterSubscriberSaveAfter;
use Synerise\Integration\Observer\OrderPlace;
use Synerise\Integration\Observer\ProductReview;
use Zend_Db_Adapter_Exception;

class Event
{
    const TOPIC_NAME = 'synerise.queue.events';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var MessageEncoder
     */
    private $messageEncoder;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var Sender
     */
    private $sender;

    /**
     * @var DataItemPublisher
     */
    private $dataItemPublisher;

    public function __construct(
        LoggerInterface $logger,
        ObjectManagerInterface $objectManager,
        MessageEncoder $messageEncoder,
        Json $json,
        Sender $sender,
        DataItemPublisher $dataItemPublisher
    ) {
        $this->logger = $logger;
        $this->objectManager = $objectManager;
        $this->messageEncoder = $messageEncoder;
        $this->json = $json;
        $this->sender = $sender;
        $this->dataItemPublisher = $dataItemPublisher;
    }

    /**
     * @param string $event
     * @return void
     */
    public function process(string $event)
    {
        $isRetryable = false;

        try {
            $deserializedData = $this->json->unserialize($event);
            if(!$this->handleDeprecatedEvent($deserializedData)) {
                $this->execute($deserializedData);
            }
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
            $deserializedData['retries'] = (isset($deserializedData['retries'])) ? ++$deserializedData['retries'] : 0;
            if ($deserializedData['retries'] <= Connection::MAX_RETRIES) {
                $this->scheduleRetry($this->json->serialize($deserializedData));
            }
        }
    }

    /**
     * @throws ApiException
     * @throws ValidatorException
     * @throws CatalogApiException
     */
    private function execute(array $event)
    {
        $this->sender->send(
            $event['event_name'],
            $event['event_payload'],
            $event['store_id']
        );
    }

    /**
     * @param string $event
     * @return bool
     */
    protected function scheduleRetry(string $event): bool
    {
        try {
            $topicName = self::getTopicName();

            $retry = $this->objectManager->create('Synerise\Integration\Model\MessageQueue\Retry');
            $retry
                ->setBody($this->messageEncoder->encode($topicName, $event))
                ->setTopicName($topicName)
                ->save();

            return true;
        } catch(Exception $e) {
            $this->logger->error($e);
            return false;
        }
    }

    /**
     * Publish deprecated event as data item or modify and send
     *
     * @param array $event
     * @return bool true if event published to different queue
     */
    protected function handleDeprecatedEvent(array &$event): bool
    {
        switch ($event['event_name']) {
            case OrderPlace::EVENT:
                $this->publishAsDataItem(Order::MODEL, $event['entity_id'], $event['store_id']);
                return true;
            case NewsletterSubscriberSaveAfter::EVENT:
                if ($event['entity_id']) {
                    $this->publishAsDataItem(Subscriber::MODEL, $event['entity_id'], $event['store_id']);
                    return true;
                } else {
                    $event['event_name'] = NewsletterSubscriberDeleteAfter::EVENT;
                    break;
                }
            case 'ADD_OR_UPDATE_CLIENT':
                $entityId = $event['event_payload']['customId'] ?? null;
                if ($entityId) {
                    $this->publishAsDataItem(Customer::MODEL, $entityId, $event['store_id']);
                    return true;
                } elseif(isset($event['event_payload']['displayName'])) {
                    $event['event_name'] = ProductReview::CUSTOMER_UPDATE;
                } else {
                    $event['event_name'] = OrderPlace::CUSTOMER_UPDATE;
                }
                break;
        }

        return false;
    }

    /**
     * @param string $model
     * @param int|null $entityId
     * @param int|null $storeId
     * @return void
     */
    protected function publishAsDataItem(string $model, ?int $entityId, ?int $storeId)
    {
        try {
            if($entityId && $storeId) {
                $this->dataItemPublisher->publish(
                    $model,
                    $entityId,
                    $storeId
                );
            }
        } catch (\Exception $e) {

        }
    }

    /**
     * @return string
     */
    static protected function getTopicName(): string
    {
        return self::TOPIC_NAME;
    }
}
