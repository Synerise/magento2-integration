<?php

namespace Synerise\Integration\Test\Integration\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Quote\Model\QuoteFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Ramsey\Uuid\Uuid;
use Synerise\Integration\Helper\Event\Cart as CartHelper;
use Synerise\Integration\Observer\CartAddProduct;

class CartRemoveProductObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\Event\Config $frameworkEvent
     */
    private $eventConfig;

    const EVENT_LABEL = 'Customer removed product from cart';

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->eventConfig = $this->objectManager->create(\Magento\Framework\Event\Config::class);
    }

    public function testObserverRegistration()
    {
        $observers = $this->eventConfig->getObservers('sales_quote_remove_item');

        $this->assertArrayHasKey('synerise_sales_quote_remove_item', $observers);
        $expectedClass = \Synerise\Integration\Observer\CartRemoveProduct::class;
        $this->assertSame($expectedClass, $observers['synerise_sales_quote_remove_item']['instance']);
    }

}