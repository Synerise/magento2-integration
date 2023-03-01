<?php

namespace Synerise\Integration\Test\Integration\Helper\Synchronization\Sender;

use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Synerise\Integration\Helper\Synchronization\Sender\Order as OrderSender;
use Synerise\Integration\Helper\Synchronization\SenderFactory;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Model\ResourceModel\Cron\Queue\Collection as QueueCollection;
use Synerise\Integration\Model\ResourceModel\Cron\Queue\CollectionFactory as QueueCollectionFactory;

class OrderTest extends \PHPUnit\Framework\TestCase
{
    const STORE_ID = 1;

    const WEBSITE_ID = 1;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        /** @var Api $apiHelper */
        $apiHelper = $this->objectManager->create(Api::class);

        /** @var CollectionFactory $collectionFactory */
        $this->collectionFactory = $this->objectManager->create(CollectionFactory::class);
        
        /** @var SenderFactory $senderFactory */
        $senderFactory = $this->objectManager->create(SenderFactory::class);
        $this->orderSender = $senderFactory->create(
            'order',
            self::STORE_ID,
            $apiHelper->getApiConfigByScope(self::STORE_ID),
            self::WEBSITE_ID
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/synerise/synchronization/models order,product
     */
    public function testIsEnabled()
    {
        $this->assertTrue($this->orderSender->isEnabled());
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/synerise/synchronization/models product
     */
    public function testIsEnabledAssertFalse()
    {
        $this->assertFalse($this->orderSender->isEnabled());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/two_orders_for_two_diff_customers.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testGetCollectionFilteredByEntityIds()
    {
        /** @var Collection $collection */
        $collection = $this->orderSender->getCollectionFilteredByEntityIds($this->getEntityIds());
        
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertEquals(2, $collection->count());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/two_orders_for_two_diff_customers.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testSendItems()
    {
        /** @var Collection $collection */
        $collection = $this->orderSender->getCollectionFilteredByEntityIds($this->getEntityIds());
        foreach ($collection as $item) {
            $item
                ->setOrderCurrencyCode('USD')
                ->setBaseCurrencyCode('USD')
                ->save();
        }

        list ($body, $statusCode, $headers) = $this->orderSender->sendItems($collection);

        $this->assertEquals(202, $statusCode);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/two_orders_for_two_diff_customers.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testDeleteItemsFromQueue()
    {
        $ids = $this->getEntityIds();

        /** @var Collection $collection */
        $collection = $this->orderSender->getCollectionFilteredByEntityIds($ids);

        /** @var Synchronization $synchronizationHelper */
        $synchronizationHelper = $this->objectManager->create(Synchronization::class);

        /** @var QueueCollectionFactory $queueCollectionFactory */
        $queueCollectionFactory = $this->objectManager->create(QueueCollectionFactory::class);

        $synchronizationHelper->addItemsToQueue(
            $collection,
            OrderSender::MODEL,
            OrderSender::ENTITY_ID
        );

        /** @var QueueCollection $queueCollection */
        $queueCollection = $queueCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('model', OrderSender::MODEL)
            ->addFieldToFilter('entity_id', $ids)
            ->setPageSize(20);

        $this->assertEquals(2, $queueCollection->count());

        $this->orderSender->deleteItemsFromQueue($ids);

        $queueCollection = $queueCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('model', OrderSender::MODEL)
            ->addFieldToFilter('entity_id', $ids)
            ->setPageSize(20);

        $this->assertEquals(0, $queueCollection->count());
    }
    
    protected function getEntityIds()
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(
            'increment_id',
            ['in' => ['100000001', '100000002']]
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