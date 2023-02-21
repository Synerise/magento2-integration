<?php

namespace Synerise\Integration\Test\Integration\Helper\Synchronization\Sender;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\TestFramework\Helper\Bootstrap;
use Synerise\Integration\Helper\Synchronization\Results;
use Synerise\Integration\Helper\Synchronization\Sender\Product as ProductSender;
use Synerise\Integration\Helper\Synchronization\SenderFactory;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Model\ResourceModel\Cron\Queue\Collection as QueueCollection;
use Synerise\Integration\Model\ResourceModel\Cron\Queue\CollectionFactory as QueueCollectionFactory;

class ProductTest extends \PHPUnit\Framework\TestCase
{

    const STORE_ID = 1;

    const WEBSITE_ID = 1;

    /**
     * @var ProductSender
     */
    protected $productSender;

    /**
     * @var Results
     */
    protected $results;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        /** @var Api $apiHelper */
        $apiHelper = $this->objectManager->create(Api::class);

        /** @var SenderFactory $senderFactory */
        $senderFactory = $this->objectManager->create(SenderFactory::class);
        $this->productSender = $senderFactory->create(
            'product',
            self::STORE_ID,
            $apiHelper->getApiConfigByScope(self::STORE_ID),
            self::WEBSITE_ID
        );

        $this->results = $this->objectManager->create(Results::class);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/synerise/synchronization/models customer,product
     */
    public function testIsEnabled()
    {
        $this->assertTrue($this->productSender->isEnabled());
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/synerise/synchronization/models customer
     */
    public function testIsEnabledAssertFalse()
    {
        $this->assertFalse($this->productSender->isEnabled());
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/products.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testGetCollectionFilteredByEntityIds()
    {
        /** @var Collection $collection */
        $collection = $this->productSender->getCollectionFilteredByEntityIds([1, 2]);
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertEquals(2, $collection->count());
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/products.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testSendItems()
    {
        $this->results->deleteItem(ProductSender::MODEL, 1);
        $this->assertFalse($this->results->isSent(ProductSender::MODEL, 1, self::STORE_ID));


        /** @var Collection $collection */
        $collection = $this->productSender->getCollectionFilteredByEntityIds([1, 2]);
        list ($body, $statusCode, $headers) = $this->productSender->sendItems($collection);

        $this->assertTrue($this->results->isSent(ProductSender::MODEL, 1, self::STORE_ID));
        $this->assertEquals(200, $statusCode);
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/products.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testDeleteItemsFromQueue()
    {
        $ids = [1, 2];

        /** @var Collection $collection */
        $collection = $this->productSender->getCollectionFilteredByEntityIds($ids);

        /** @var Synchronization $synchronizationHelper */
        $synchronizationHelper = $this->objectManager->create(Synchronization::class);

        /** @var QueueCollectionFactory $queueCollectionFactory */
        $queueCollectionFactory = $this->objectManager->create(QueueCollectionFactory::class);

        $synchronizationHelper->addItemsToQueuePerStore(
            $collection,
            ProductSender::MODEL,
            ProductSender::ENTITY_ID
        );

        /** @var QueueCollection $queueCollection */
        $queueCollection = $queueCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('model', ProductSender::MODEL)
            ->addFieldToFilter('entity_id', $ids)
            ->setPageSize(20);

        $this->assertEquals(2, $queueCollection->count());

        $this->productSender->deleteItemsFromQueue($ids);

        $queueCollection = $queueCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('model', ProductSender::MODEL)
            ->addFieldToFilter('entity_id', $ids)
            ->setPageSize(20);

        $this->assertEquals(0, $queueCollection->count());
    }
}