<?php

namespace Synerise\Integration\Test\Integration\Observer\Event;

use Magento\Framework\Event\Config;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Synerise\Integration\Observer\Event\CartStatus;

class CartStatusTest extends \PHPUnit\Framework\TestCase
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
        $observers = $this->eventConfig->getObservers('sales_quote_save_after');

        $this->assertArrayHasKey('synerise_sales_quote_save_after', $observers);
        $expectedClass = CartStatus::class;
        $this->assertSame($expectedClass, $observers['synerise_sales_quote_save_after']['instance']);
    }
}