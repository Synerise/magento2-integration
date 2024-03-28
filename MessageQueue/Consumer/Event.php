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
use Synerise\ApiClient\ApiException;
use Synerise\CatalogsApiClient\ApiException as CatalogApiException;
use Synerise\Integration\Communication\Config;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\MessageQueue\Publisher\Data\Item as DataItemPublisher;
use Synerise\Integration\Model\MessageQueue\Retry;
use Synerise\Integration\Observer\MergeUuids;
use Synerise\Integration\SyneriseApi\Sender\Data\Customer as CustomerSender;
use Synerise\Integration\SyneriseApi\Sender\Data\Order as OrderSender;
use Synerise\Integration\SyneriseApi\Sender\Data\Product as ProductSender;
use Synerise\Integration\SyneriseApi\Sender\Data\Subscriber as SubscriberSender;
use Synerise\Integration\SyneriseApi\Sender\Event as EventSender;
use Synerise\Integration\Observer\Data\ProductDelete;
use Synerise\Integration\Observer\Data\SubscriberDelete;
use Synerise\Integration\Observer\Data\SubscriberSave;
use Synerise\Integration\Observer\Data\OrderSave;
use Synerise\Integration\Observer\Event\ProductReview;
use Zend_Db_Adapter_Exception;

class Event
{
    public const TOPIC_NAME = 'synerise.queue.events';

    /**
     * @var Logger
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
     * @var EventSender
     */
    private $eventSender;

    /**
     * @var CustomerSender
     */
    private $customerSender;

    /**
     * @var ProductSender
     */
    private $productSender;

    /**
     * @var SubscriberSender
     */
    private $subscriberSender;

    /**
     * @var DataItemPublisher
     */
    private $dataItemPublisher;

    /**
     * @param Logger $logger
     * @param ObjectManagerInterface $objectManager
     * @param MessageEncoder $messageEncoder
     * @param Json $json
     * @param EventSender $eventSender
     * @param CustomerSender $customerSender
     * @param ProductSender $productSender
     * @param SubscriberSender $subscriberSender
     * @param DataItemPublisher $dataItemPublisher
     */
    public function __construct(
        Logger $logger,
        ObjectManagerInterface $objectManager,
        MessageEncoder $messageEncoder,
        Json $json,
        EventSender $eventSender,
        CustomerSender$customerSender,
        ProductSender $productSender,
        SubscriberSender $subscriberSender,
        DataItemPublisher $dataItemPublisher
    ) {
        $this->logger = $logger;
        $this->objectManager = $objectManager;
        $this->messageEncoder = $messageEncoder;
        $this->json = $json;
        $this->eventSender = $eventSender;
        $this->customerSender = $customerSender;
        $this->productSender = $productSender;
        $this->subscriberSender = $subscriberSender;
        $this->dataItemPublisher = $dataItemPublisher;
    }

    /**
     * Process
     *
     * @param string $event
     * @return void
     */
    public function process(string $event)
    {
        $isRetryable = false;

        try {
            $deserializedData = $this->json->unserialize($event);
            if (!$this->handleDeprecatedEvent($deserializedData)) {
                $this->execute($deserializedData);
            }
        } catch (TransferException $e) {
            $this->logger->error($e->getMessage());
            $isRetryable = true;
        } catch (ApiException | CatalogApiException $e) {
            $isRetryable = ($e->getCode() == 0 || $e->getCode() == 401 || $e->getCode() == 403 || $e->getCode() >= 500);
        } catch (Zend_Db_Adapter_Exception $e) {
            $this->logger->critical($e->getMessage());
            $isRetryable = (
                $e instanceof LockWaitException ||
                $e instanceof DeadlockException ||
                $e instanceof ConnectionException
            );
        } catch (NoSuchEntityException $e) {
            $this->logger->critical($e->getMessage());
            $isRetryable = ($e instanceof TemporaryStateExceptionInterface);
        } catch (LocalizedException|Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        if ($isRetryable) {
            $deserializedData['retries'] = (isset($deserializedData['retries'])) ? ++$deserializedData['retries'] : 0;
            if ($deserializedData['retries'] <= Config::MAX_RETRIES) {
                $this->scheduleRetry($this->json->serialize($deserializedData));
            }
        }
    }

    /**
     * Send event
     *
     * @param array $event
     * @throws ApiException
     * @throws CatalogApiException
     * @throws ValidatorException
     */
    private function execute(array $event)
    {
        switch ($event['event_name']) {
            case ProductDelete::EVENT:
                $this->productSender->deleteItem($event['event_payload'], $event['store_id'], $event['entity_id']);
                break;
            case SubscriberDelete::EVENT:
                $this->subscriberSender->deleteItem($event['event_payload'], $event['store_id'], $event['entity_id']);
                break;
            case MergeUuids::EVENT:
                $this->customerSender->batchAddOrUpdateClients($event['event_payload'], $event['store_id']);
                break;
            case OrderSave::CUSTOMER_UPDATE:
            case ProductReview::CUSTOMER_UPDATE:
                $this->customerSender->batchAddOrUpdateClients([$event['event_payload']], $event['store_id']);
                break;
            default:
                $this->eventSender->send(
                    $event['event_name'],
                    $event['event_payload'],
                    $event['store_id']
                );
        }
    }

    /**
     * Add message to retry table
     *
     * @param string $event
     * @return bool
     */
    protected function scheduleRetry(string $event): bool
    {
        try {
            $topicName = self::TOPIC_NAME;

            $retry = $this->objectManager->create(Retry::class);
            $retry
                ->setBody($this->messageEncoder->encode($topicName, $event))
                ->setTopicName($topicName)
                ->save();

            return true;
        } catch (Exception $e) {
            $this->logger->error($e);
            return false;
        }
    }

    /**
     * Check for deprecated events. Publish as data item or modify and send.
     *
     * @param array $event
     * @return bool true if event published to different queue
     */
    protected function handleDeprecatedEvent(array &$event): bool
    {
        switch ($event['event_name']) {
            case OrderSave::EVENT:
                $this->publishAsDataItem(OrderSender::MODEL, $event['entity_id'], $event['store_id']);
                return true;
            case SubscriberSave::EVENT:
                if ($event['entity_id']) {
                    $this->publishAsDataItem(SubscriberSender::MODEL, $event['entity_id'], $event['store_id']);
                    return true;
                } else {
                    $event['event_name'] = SubscriberDelete::EVENT;
                    break;
                }
            case ProductDelete::EVENT_FOR_CONFIG:
                $event['event_payload'] = $event['event_payload'][0];
                $event['event_name'] = ProductDelete::EVENT;
                break;
            case 'ADD_OR_UPDATE_CLIENT':
                $entityId = $event['event_payload']['customId'] ?? null;
                if ($entityId) {
                    $this->publishAsDataItem(CustomerSender::MODEL, $entityId, $event['store_id']);
                    return true;
                } elseif (isset($event['event_payload']['displayName'])) {
                    $event['event_name'] = ProductReview::CUSTOMER_UPDATE;
                } else {
                    $event['event_name'] = OrderSave::CUSTOMER_UPDATE;
                }
                break;
        }

        return false;
    }

    /**
     * Publish message as data item instead of processing
     *
     * @param string $model
     * @param int|null $entityId
     * @param int|null $storeId
     * @return void
     */
    protected function publishAsDataItem(string $model, ?int $entityId, ?int $storeId)
    {
        if ($entityId && $storeId) {
            $this->dataItemPublisher->publish(
                $model,
                $entityId,
                $storeId
            );
        }
    }
}
