<?php

namespace Synerise\Integration\Test\Integration\SyneriseApi\Mapper\Data;

use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Synerise\Integration\MessageQueue\CollectionFactoryProvider;
use Synerise\Integration\MessageQueue\Filter;
use Synerise\Integration\SyneriseApi\Mapper\Data\ProductCRUD;
use Synerise\Integration\SyneriseApi\Sender\Data\Product as Sender;

class ProductCRUDTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ProductCRUD
     */
    private $mapper;

    /**
     * @var Sender
     */
    private $sender;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var CollectionFactoryProvider
     */
    private  $collectionFactoryProvider;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->mapper = $this->objectManager->create(ProductCRUD::class);
        $this->sender = $this->objectManager->create(Sender::class);
        $this->filter = $this->objectManager->create(Filter::class);
        $this->collectionFactoryProvider = $this->objectManager->create(CollectionFactoryProvider::class);
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/products.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testPrepareRequest(): void
    {
        $attributes = $this->sender->getAttributesToSelect(1);
        $collection = $this->filter->filterByEntityIds(
            $this->collectionFactoryProvider->get('product')->create(),
            [1,2],
            1,
            2
        );

        if (!empty($attributes)) {
            $collection->addAttributeToSelect($attributes);
        }

        foreach ($collection as $product) {
            $request = $this->mapper->prepareRequest($product, 1);

            $this->assertTrue($request->valid());

            $this->assertEquals($product->getSku(), $request->getItemKey());

            $value = $request->getValue();
            $this->assertEquals($product->getSku(), $value['itemId']);
            $this->assertEquals($product->getSku(), $value['sku']);
            $this->assertEquals($product->getUrlInStore(), $value['productUrl']);
            $this->assertEquals(0, $value['deleted']);
            $this->assertEquals(10, $value['price']);
            $this->assertEquals(1, $value['stock_status']);
            $this->assertTrue($value['is_salable']);
        }

    }
}