<?php

namespace Synerise\Integration\Test\Integration\Helper\Synchronization\Sender;

use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Synerise\Integration\Helper\Synchronization\Sender\Subscriber as SubscriberSender;
use Synerise\Integration\Helper\Synchronization\SenderFactory;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Model\ResourceModel\Cron\Queue\Collection as QueueCollection;
use Synerise\Integration\Model\ResourceModel\Cron\Queue\CollectionFactory as QueueCollectionFactory;

class SubscriberTest extends \PHPUnit\Framework\TestCase
{
    const STORE_ID = 1;

    const WEBSITE_ID = 1;

    /**
     * @var SubscriberSender
     */
    protected $subscriberSender;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        /** @var Api $apiHelper */
        $apiHelper = $this->objectManager->create(Api::class);

        /** @var CollectionFactory $collectionFactory */
        $this->collectionFactory = $this->objectManager->create(CollectionFactory::class);

        /** @var SenderFactory $senderFactory */
        $senderFactory = $this->objectManager->create(SenderFactory::class);
        $this->subscriberSender = $senderFactory->create(
            'subscriber',
            self::STORE_ID,
            $apiHelper->getApiConfigByScope(self::STORE_ID),
            self::WEBSITE_ID
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/synerise/synchronization/models subscriber,product
     */
    public function testIsEnabled()
    {
        $this->assertTrue($this->subscriberSender->isEnabled());
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/synerise/synchronization/models product
     */
    public function testIsEnabledAssertFalse()
    {
        $this->assertFalse($this->subscriberSender->isEnabled());
    }

    /**
     * @magentoDataFixture Magento/Newsletter/_files/subscribers.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testGetCollectionFilteredByEntityIds()
    {
        /** @var Collection $collection */
        $collection = $this->subscriberSender->getCollectionFilteredByEntityIds($this->getEntityIds());
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertEquals(3, $collection->count());
    }

    /**
     * @magentoDataFixture Magento/Newsletter/_files/subscribers.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testSendItems()
    {
        /** @var Collection $collection */
        $collection = $this->subscriberSender->getCollectionFilteredByEntityIds($this->getEntityIds());
        list ($body, $statusCode, $headers) = $this->subscriberSender->sendItems($collection);

        $this->assertEquals(202, $statusCode);
    }

    /**
     * @magentoDataFixture Magento/Newsletter/_files/subscribers.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testDeleteItemsFromQueue()
    {
        $ids = $this->getEntityIds();

        /** @var Collection $collection */
        $collection = $this->subscriberSender->getCollectionFilteredByEntityIds($ids);

        /** @var Synchronization $synchronizationHelper */
        $synchronizationHelper = $this->objectManager->create(Synchronization::class);

        /** @var QueueCollectionFactory $queueCollectionFactory */
        $queueCollectionFactory = $this->objectManager->create(QueueCollectionFactory::class);

        $synchronizationHelper->addItemsToQueue(
            $collection,
            SubscriberSender::MODEL,
            SubscriberSender::ENTITY_ID
        );

        /** @var QueueCollection $queueCollection */
        $queueCollection = $queueCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('model', SubscriberSender::MODEL)
            ->addFieldToFilter('entity_id', $ids)
            ->setPageSize(20);

        $this->assertEquals(3, $queueCollection->count());

        $this->subscriberSender->deleteItemsFromQueue($ids);

        $queueCollection = $queueCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('model', SubscriberSender::MODEL)
            ->addFieldToFilter('entity_id', $ids)
            ->setPageSize(20);

        $this->assertEquals(0, $queueCollection->count());
    }


    protected function getEntityIds()
    {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $collection */
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(
                'subscriber_email',
                ['in' => ['customer@example.com', 'customer_two@example.com', 'customer_confirm@example.com']]
            );

        $collection->getSelect()
            ->where('main_table.store_id=?', self::STORE_ID);

        $ids = [];
        foreach ($collection as $item) {
            $ids[] = $item->getId();
        }

        return $ids;
    }
}