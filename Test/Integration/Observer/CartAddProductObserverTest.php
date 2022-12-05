<?php

namespace Synerise\Integration\Test\Integration\Observer;

class NewProductsCategoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\Event\Config $frameworkEvent
     */
    protected $eventConfig;

    protected function setUp(): void
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->eventConfig = $objectManager->create(\Magento\Framework\Event\Config::class);
    }

    public function testTheModuleRegistersASalesOrderPlaceAfterObserver()
    {
        $observers = $this->eventConfig->getObservers('sales_quote_add_item');

        $this->assertArrayHasKey('synerise_checkout_cart_add_product_complete', $observers);
        $expectedClass = \Synerise\Integration\Observer\CartAddProduct::class;
        $this->assertSame($expectedClass, $observers['synerise_checkout_cart_add_product_complete']['instance']);
    }
}