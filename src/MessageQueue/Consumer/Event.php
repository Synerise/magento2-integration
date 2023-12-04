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
use Synerise\Integration\MessageQueue\Sender\Event as Sender;
use Synerise\Integration\Model\Config\Source\MessageQueue\Connection;
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

    public function __construct(
        LoggerInterface $logger,
        ObjectManagerInterface $objectManager,
        MessageEncoder $messageEncoder,
        Json $json,
        Sender $sender
    ) {
        $this->logger = $logger;
        $this->objectManager = $objectManager;
        $this->messageEncoder = $messageEncoder;
        $this->json = $json;
        $this->sender = $sender;
    }

    public function process(string $event)
    {
        $isRetryable = false;

        try {
            $deserializedData = $this->json->unserialize($event);
            $this->execute($deserializedData);
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
            $event['store_id'],
            $event['entity_id']
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
     * @return string
     */
    static protected function getTopicName(): string
    {
        return self::TOPIC_NAME;
    }
}
