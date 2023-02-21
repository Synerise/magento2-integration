<?php

namespace Synerise\Integration\Test\Integration\Observer\Event\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Quote\Model\QuoteFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Ramsey\Uuid\Uuid;
use Synerise\Integration\Helper\Api\Event\Cart as CartHelper;
use Synerise\Integration\Observer\Event\Cart\QtyUpdate;
use Synerise\Integration\Observer\Event\Cart\Status;

class StatusObserverTest extends \PHPUnit\Framework\TestCase
{
    const EVENT_LABEL = 'CartStatus';

    const PRODUCT_QTY = 2;

    const STORE_ID = 1;

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
     * @var Status
     */
    private $cartStatusObserver;

    /**
     * @var QtyUpdate
     */
    private $cartQtyUpdateObserver;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->eventConfig = $this->objectManager->create(\Magento\Framework\Event\Config::class);
        $this->productRepository = $this->objectManager->get(ProductRepository::class);
        $this->quoteFactory = $this->objectManager->get(QuoteFactory::class);
        $this->cartStatusObserver = $this->objectManager->get(Status::class);
        $this->cartQtyUpdateObserver = $this->objectManager->get(QtyUpdate::class);
        $this->cartHelper = $this->objectManager->get(CartHelper::class);
    }

    public function testObserverRegistration()
    {
        $observers = $this->eventConfig->getObservers('sales_quote_save_after');

        $this->assertArrayHasKey('synerise_sales_quote_save_after', $observers);
        $expectedClass = Status::class;
        $this->assertSame($expectedClass, $observers['synerise_sales_quote_save_after']['instance']);
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testCartStatus(): void
    {
        $quote = $this->quoteFactory->create();
        $product = $this->productRepository->getById(1);

        $productHelper = $this->objectManager->get(\Magento\Catalog\Helper\Product::class);
        $isSkipSaleableCheck = $productHelper->getSkipSaleableCheck();
        $productHelper->setSkipSaleableCheck(true);
        $quote->addProduct($product, self::PRODUCT_QTY);

        $productHelper->setSkipSaleableCheck($isSkipSaleableCheck);

        $quote->collectTotals();
        $this->assertTrue($this->cartHelper->hasItemDataChanges($quote));

        $uuid = (string) Uuid::Uuid4();

        list ($body, $statusCode, $headers) = $this->cartStatusObserver->sendCartStatusEvent(
            $this->cartHelper->prepareCartStatusRequest(
                $quote,
                $uuid
            ),
            self::STORE_ID
        );

        $this->assertEquals(202, $statusCode);

        $response = $this->cartQtyUpdateObserver->sendCartStatusEvent(
            $this->cartHelper->prepareCartStatusRequest(
                $quote,
                $uuid
            ),
            self::STORE_ID
        );

        $this->assertNull($response);

    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testCartStatusOnOrder(): void
    {
        $quote = $this->quoteFactory->create();
        $product = $this->productRepository->getById(1);

        $productHelper = $this->objectManager->get(\Magento\Catalog\Helper\Product::class);
        $isSkipSaleableCheck = $productHelper->getSkipSaleableCheck();
        $productHelper->setSkipSaleableCheck(true);
        $quote->addProduct($product, self::PRODUCT_QTY);
        $quote->setReservedOrderId('test-order');

        $productHelper->setSkipSaleableCheck($isSkipSaleableCheck);

        $uuid = (string) Uuid::Uuid4();

        list ($body, $statusCode, $headers) = $this->cartStatusObserver->sendCartStatusEvent(
            $this->cartHelper->prepareCartStatusRequest(
                $quote,
                $uuid
            ),
            self::STORE_ID
        );

        $this->assertEquals(202, $statusCode);
    }

}