<?php

namespace Synerise\Integration\Test\Integration\Observer\Event;

use Magento\Framework\Event\Config;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Synerise\Integration\Observer\Event\CartQtyUpdate;

class CartQtyUpdateTest extends \PHPUnit\Framework\TestCase
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
        $observers = $this->eventConfig->getObservers('checkout_cart_update_items_after');

        $this->assertArrayHasKey('synerise_checkout_cart_update_items_after', $observers);
        $expectedClass = CartQtyUpdate::class;
        $this->assertSame($expectedClass, $observers['synerise_checkout_cart_update_items_after']['instance']);
    }
}
