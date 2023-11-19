<?php

namespace Synerise\Integration\Model\MessageQueue\Data\Single\Consumer;

use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Queue;
use Synerise\Integration\Model\MessageQueue\Data\Single\ConsumerInterface;
use Synerise\Integration\Model\MessageQueue\Data\Single\Message;
use Synerise\Integration\Model\Synchronization\Sender\Customer as Sender;
use Synerise\Integration\Model\Synchronization\Sender\SenderInterface;

class Customer implements ConsumerInterface
{
    const MAX_RETRIES = 3;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Queue
     */
    private $queueHelper;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Sender
     */
    private $sender;

    public function __construct(
        LoggerInterface $logger,
        Queue $queueHelper,
        CollectionFactory $collectionFactory,
        Sender $sender
    ) {
        $this->logger = $logger;
        $this->queueHelper = $queueHelper;
        $this->collectionFactory = $collectionFactory;
        $this->sender = $sender;
    }

    public function process(Message $update)
    {
        try {
            $this->execute($update);
        } catch (ApiException $e) {
        } catch (\Exception $e) {
            $this->logger->error('An error occurred while processing the queue message', ['exception' => $e]);
        }
    }

    /**
     * @param Message $message
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function execute(Message $message)
    {
        try {
            $this->sender->sendItems(
                $this->getCollectionFilteredByEntityId($this->sender, $message->getStoreId(), $message->getEntityId()),
                $message->getStoreId()
            );
        } catch(ApiException $e) {
            if ($e->getCode() > 500) {
                $this->logger->debug('Publish for Retry: ' . $message->getModel() . ' id:'. $message->getEntityId() );
                $retries = $deserializedData['retries'] ?? 0;
                if ($retries < self::MAX_RETRIES) {
                    $retries++;
                    $this->queueHelper->publishUpdate($message->getModel(), $message->getStoreId(), $message->getEntityId(), $retries);
                }
            }
        }
    }


    /**
     * @param SenderInterface $sender
     * @param int $storeId
     * @param int $entityId
     * @return Collection
     */
    protected function getCollectionFilteredByEntityId(SenderInterface $sender, int $storeId, int $entityId)
    {
        return $this->collectionFactory->create()
            ->addStoreFilter($storeId)
            ->addFieldToFilter(
                $sender::ENTITY_ID,
                ['eq' => $entityId]
            )
            ->setOrder($sender::ENTITY_ID, 'ASC')
            ->setPageSize($sender->getPageSize());
    }
}
