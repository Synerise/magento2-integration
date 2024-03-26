<?php

namespace Synerise\Integration\Test\Integration\Observer\Event;

use Magento\Framework\Event\Config;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Synerise\Integration\Observer\Event\CartAddProduct;

class CartAddProductTest extends \PHPUnit\Framework\TestCase
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
        $observers = $this->eventConfig->getObservers('sales_quote_add_item');

        $this->assertArrayHasKey('synerise_checkout_cart_add_product_complete', $observers);
        $expectedClass = CartAddProduct::class;
        $this->assertSame($expectedClass, $observers['synerise_checkout_cart_add_product_complete']['instance']);
    }
}