<?php

namespace Synerise\Integration\Test\Integration\Observer;


use Magento\TestFramework\Helper\Bootstrap;
use Synerise\Integration\Observer\CartQtyUpdate;

class CartQtyUpdateObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\Event\Config $frameworkEvent
     */
    private $eventConfig;


    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->eventConfig = $this->objectManager->create(\Magento\Framework\Event\Config::class);
    }

    /**
     * @return void
     */
    public function testObserverRegistration()
    {
        $observers = $this->eventConfig->getObservers('checkout_cart_update_items_after');

        $this->assertArrayHasKey('synerise_checkout_cart_update_items_after', $observers);
        $expectedClass = CartQtyUpdate::class;
        $this->assertSame($expectedClass, $observers['synerise_checkout_cart_update_items_after']['instance']);
    }

}