<?php

namespace Synerise\Integration\Test\Integration\SyneriseApi\Mapper\Data;

use Magento\Framework\ObjectManagerInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\TestFramework\Helper\Bootstrap;
use Synerise\Integration\Helper\Tracking\UuidGenerator;
use Synerise\Integration\MessageQueue\CollectionFactoryProvider;
use Synerise\Integration\MessageQueue\Filter;
use Synerise\Integration\SyneriseApi\Mapper\Data\SubscriberCRUD;

class SubscriberCRUDTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var SubscriberCRUD
     */
    private $mapper;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var CollectionFactoryProvider
     */
    private  $collectionFactoryProvider;

    /**
     * @var UuidGenerator
     */
    private $uuidGenerator;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->mapper = $this->objectManager->create(SubscriberCRUD::class);
        $this->filter = $this->objectManager->create(Filter::class);
        $this->collectionFactoryProvider = $this->objectManager->create(CollectionFactoryProvider::class);
        $this->uuidGenerator = $this->objectManager->create(UuidGenerator::class);
    }

    /**
     * @magentoDataFixture Magento/Newsletter/_files/subscribers.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testPrepareRequest(): void
    {
        $collection = $this->filter->filterByEntityIds(
            $this->collectionFactoryProvider->get('subscriber')->create(),
            $this->getEntityIds(),
            1,
            2
        );

        /** @var Subscriber $subscriber */
        foreach ($collection as $subscriber) {
            $uuid = $this->uuidGenerator->generateByEmail($subscriber->getEmail());
            $request = $this->mapper->prepareRequest($subscriber);

            $this->assertTrue($request->valid());

            $this->assertEquals($uuid, $request->getUuid());
            $this->assertEquals($subscriber->getEmail(), $request->getEmail());

            $agreements = $request->getAgreements();
            $this->assertEquals(1, $agreements->getEmail());

var_dump($request);

        }

    }

    protected function getEntityIds()
    {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $collection */
        $collection = $this->collectionFactoryProvider->get('subscriber')->create()
            ->addFieldToFilter(
                'subscriber_email',
                ['in' => ['customer@example.com', 'customer_two@example.com', 'customer_confirm@example.com']]
            );

        $collection->getSelect()
            ->where('main_table.store_id=?', 1);

        $ids = [];
        foreach ($collection as $item) {
            $ids[] = $item->getId();
        }

        return $ids;
    }
}