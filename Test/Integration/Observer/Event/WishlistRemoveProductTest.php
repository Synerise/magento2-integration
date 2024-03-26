<?php

namespace Synerise\Integration\Test\Integration\Observer\Event;

use Magento\Framework\Event\Config;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Synerise\Integration\Observer\Event\WishlistRemoveProduct;

class WishlistRemoveProductTest extends \PHPUnit\Framework\TestCase
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
        $observers = $this->eventConfig->getObservers('wishlist_item_delete_after');

        $this->assertArrayHasKey('synerise_wishlist_item_delete_after', $observers);
        $expectedClass = WishlistRemoveProduct::class;
        $this->assertSame($expectedClass, $observers['synerise_wishlist_item_delete_after']['instance']);
    }
}