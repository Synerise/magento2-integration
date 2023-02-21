<?php

namespace Synerise\Integration\Test\Integration\Helper\Synchronization\Sender;

use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\TestFramework\Helper\Bootstrap;
use Synerise\Integration\Helper\Synchronization\Sender\Customer as CustomerSender;
use Synerise\Integration\Helper\Synchronization\SenderFactory;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Model\ResourceModel\Cron\Queue\Collection as QueueCollection;
use Synerise\Integration\Model\ResourceModel\Cron\Queue\CollectionFactory as QueueCollectionFactory;

class CustomerTest extends \PHPUnit\Framework\TestCase
{

    const STORE_ID = 1;

    const WEBSITE_ID = 1;

    /**
     * @var CustomerSender
     */
    protected $customerSender;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        /** @var Api $apiHelper */
        $apiHelper = $this->objectManager->create(Api::class);

        /** @var SenderFactory $senderFactory */
        $senderFactory = $this->objectManager->create(SenderFactory::class);
        $this->customerSender = $senderFactory->create(
            'customer',
            self::STORE_ID,
            $apiHelper->getApiConfigByScope(self::STORE_ID),
            self::WEBSITE_ID
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/synerise/synchronization/models customer,product
     */
    public function testIsEnabled()
    {
        $this->assertTrue($this->customerSender->isEnabled());
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/synerise/synchronization/models product
     */
    public function testIsEnabledAssertFalse()
    {
        $this->assertFalse($this->customerSender->isEnabled());
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/two_customers.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testGetCollectionFilteredByEntityIds()
    {
        /** @var Collection $collection */
        $collection = $this->customerSender->getCollectionFilteredByEntityIds([1, 2]);
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertEquals(2, $collection->count());
    }

    /**
     * @magentoConfigFixture current_store synerise/customer/attributes dob,gender,default_billing
     * @magentoDataFixture Magento/Customer/_files/two_customers.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testSendItems()
    {
        /** @var Collection $collection */
        $collection = $this->customerSender->getCollectionFilteredByEntityIds([1, 2]);
        list ($body, $statusCode, $headers) = $this->customerSender->sendItems($collection);

        $this->assertEquals(202, $statusCode);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/two_customers.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testDeleteItemsFromQueue()
    {
        $ids = [1, 2];

        /** @var Collection $collection */
        $collection = $this->customerSender->getCollectionFilteredByEntityIds($ids);

        /** @var Synchronization $synchronizationHelper */
        $synchronizationHelper = $this->objectManager->create(Synchronization::class);

        /** @var QueueCollectionFactory $queueCollectionFactory */
        $queueCollectionFactory = $this->objectManager->create(QueueCollectionFactory::class);

        $synchronizationHelper->addItemsToQueue(
            $collection,
            CustomerSender::MODEL,
            CustomerSender::ENTITY_ID
        );

        /** @var QueueCollection $queueCollection */
        $queueCollection = $queueCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('model', CustomerSender::MODEL)
            ->addFieldToFilter('entity_id', $ids)
            ->setPageSize(20);

        $this->assertEquals(2, $queueCollection->count());

        $this->customerSender->deleteItemsFromQueue($ids);

        $queueCollection = $queueCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('model', CustomerSender::MODEL)
            ->addFieldToFilter('entity_id', $ids)
            ->setPageSize(20);

        $this->assertEquals(0, $queueCollection->count());
    }
}