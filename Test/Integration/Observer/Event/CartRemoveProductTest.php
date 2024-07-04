<?php

namespace Synerise\Integration\Test\Integration\Observer\Event;

use Magento\Framework\Event\Config;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Synerise\Integration\Observer\Event\CartRemoveProduct;

class CartRemoveProductTest extends \PHPUnit\Framework\TestCase
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
        $observers = $this->eventConfig->getObservers('sales_quote_remove_item');

        $this->assertArrayHasKey('synerise_sales_quote_remove_item', $observers);
        $expectedClass = CartRemoveProduct::class;
        $this->assertSame($expectedClass, $observers['synerise_sales_quote_remove_item']['instance']);
    }
}
