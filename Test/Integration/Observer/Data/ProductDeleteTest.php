<?php

namespace Synerise\Integration\Test\Integration\Observer\Event;

use Magento\Framework\Event\Config;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Synerise\Integration\Observer\Data\ProductDelete;

class ProductDeleteTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /**
     * @var Config
     */
    private $eventConfig;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->eventConfig = $this->objectManager->create(Config::class);
    }

    public function testObserverRegistration()
    {
        $observers = $this->eventConfig->getObservers('catalog_product_delete_before');

        $this->assertArrayHasKey('synerise_catalog_product_delete_before', $observers);
        $expectedClass = ProductDelete::class;
        $this->assertSame($expectedClass, $observers['synerise_catalog_product_delete_before']['instance']);
    }
}