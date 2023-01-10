<?php

namespace Synerise\Integration\Test\Integration\Observer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Quote\Model\QuoteFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Ramsey\Uuid\Uuid;
use Synerise\Integration\Helper\Event\Cart as CartHelper;
use Synerise\Integration\Observer\CartStatus;

class CartStatusObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\Event\Config $frameworkEvent
     */
    private $eventConfig;

    const EVENT_LABEL = 'CartStatus';

    const PRODUCT_QTY = 2;

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

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->eventConfig = $this->objectManager->create(\Magento\Framework\Event\Config::class);
        $this->productRepository = $this->objectManager->get(ProductRepository::class);
        $this->quoteFactory = $this->objectManager->get(QuoteFactory::class);

        $this->cartHelper = $this->objectManager->get(CartHelper::class);
    }

    public function testObserverRegistration()
    {
        $observers = $this->eventConfig->getObservers('sales_quote_save_after');

        $this->assertArrayHasKey('synerise_sales_quote_save_after', $observers);
        $expectedClass = CartStatus::class;
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

        $request = $this->cartHelper->prepareCartStatusRequest(
            $quote,
            $uuid
        );

        $this->assertTrue($request->valid());
        $this->assertEquals(self::EVENT_LABEL, $request->getLabel());

        $client = $request->getClient();
        $this->assertEquals($client->getUuid(), $uuid);
        $this->assertNull($client->getCustomId());
        $this->assertNull($client->getEmail());

        $params = $request->getParams();

        $this->assertEquals($quote->getSubtotal(), $params['totalAmount']);
        $this->assertEquals((int) $quote->getItemsQty(), $params['totalQuantity']);

        $this->assertEquals($product->getSku(), $params['products'][0]['sku']);
        $this->assertEquals($product->getName(), $params['products'][0]['name']);
        $this->assertEquals(self::PRODUCT_QTY, $params['products'][0]['quantity']);
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

        $request = $this->cartHelper->prepareCartStatusRequest(
            $quote,
            $uuid
        );

        $this->assertTrue($request->valid());
        $this->assertEquals(self::EVENT_LABEL, $request->getLabel());

        $client = $request->getClient();
        $this->assertEquals($client->getUuid(), $uuid);
        $this->assertNull($client->getCustomId());
        $this->assertNull($client->getEmail());

        $params = $request->getParams();

        $this->assertEquals(0, $params['totalAmount']);
        $this->assertEquals(0, $params['totalQuantity']);
        $this->assertEquals([], $params['products']);
    }

}