<?php

namespace Synerise\Integration\Test\Integration\Observer\Event\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\Catalog\Model\ProductRepository;
use Magento\Quote\Model\QuoteFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Ramsey\Uuid\Uuid;
use Synerise\Integration\Helper\Api\Event\Cart as CartHelper;
use Synerise\Integration\Helper\Event;
use Synerise\Integration\Observer\Event\Cart\RemoveProduct;

class RemoveProductObserverTest extends \PHPUnit\Framework\TestCase
{
    const EVENT_LABEL = 'Customer removed product from cart';

    const STORE_ID = 1;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Magento\Framework\Event\Config
     */
    private $eventConfig;

    /**
     * @var CartHelper
     */
    private $cartHelper;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProductHelper
     */
    private $productHelper;

    /**
     * @var Event
     */
    private $event;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->eventConfig = $this->objectManager->create(\Magento\Framework\Event\Config::class);
        $this->productRepository = $this->objectManager->get(ProductRepository::class);
        $this->quoteFactory = $this->objectManager->get(QuoteFactory::class);
        $this->cartHelper = $this->objectManager->get(CartHelper::class);
        $this->productHelper = $this->objectManager->get(ProductHelper::class);
        $this->observer = $this->objectManager->create(RemoveProduct::class);
        $this->event = $this->objectManager->create(Event::class);
    }

    public function testObserverRegistration()
    {
        $observers = $this->eventConfig->getObservers('sales_quote_remove_item');

        $this->assertArrayHasKey('synerise_sales_quote_remove_item', $observers);
        $expectedClass = RemoveProduct::class;
        $this->assertSame($expectedClass, $observers['synerise_sales_quote_remove_item']['instance']);
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testPrepareAddToCartRequestSimpleProduct(): void
    {
        $qty = 2;
        $product = $this->productRepository->getById(1);

        $this->productHelper->setSkipSaleableCheck(true);
        $quote = $this->quoteFactory->create();
        $quoteItem = $quote->addProduct($product, $qty);

        $uuid = (string) Uuid::Uuid4();

        list ($body, $statusCode, $headers) = $this->event->sendEvent(
            RemoveProduct::EVENT,
            $this->cartHelper->prepareAddToCartRequest(
                $quoteItem,
                RemoveProduct::EVENT,
                $uuid
            ),
            self::STORE_ID
        );

        $this->assertEquals(202, $statusCode);
    }
}