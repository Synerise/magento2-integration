<?php

namespace Synerise\Integration\Test\Integration\Synchronization;

use Magento\Framework\App\Config\Storage\Writer;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Cron\Synchronization\Sender\Product;

class ProductTest extends \PHPUnit\Framework\TestCase
{

    const STORE_ID = 1;

    const WEBSITE_ID = 1;

    /**
     * @var Product
     */
    protected $productSynchronization;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->productSynchronization = $this->objectManager->create(Product::class);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/synerise/synchronization/models customer,product
     */
    public function testIsEnabled()
    {
        $this->assertTrue($this->productSynchronization->isEnabled());
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/synerise/synchronization/models customer
     */
    public function testIsEnabledAssertFalse()
    {
        $this->assertFalse($this->productSynchronization->isEnabled());
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/products.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testGetCollectionFilteredByEntityIds()
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->productSynchronization->getCollectionFilteredByEntityIds(self::STORE_ID, [1, 2]);
        $this->assertEquals(2, $collection->count());
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/products.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testSendItems()
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->productSynchronization->getCollectionFilteredByEntityIds(self::STORE_ID, [1, 2]);
        list ($body, $statusCode, $headers) = $this->productSynchronization->sendItems(
            $collection,
            self::STORE_ID,
            self::WEBSITE_ID
        );

        $this->assertEquals(200, $statusCode);
    }
}