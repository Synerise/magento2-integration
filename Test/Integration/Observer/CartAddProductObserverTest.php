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

class CartAddProductObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\Event\Config $frameworkEvent
     */
    private $eventConfig;

    const EVENT_LABEL = 'Customer added product to cart';

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

    /**
     * @return void
     */
    public function testObserverRegistration()
    {
        $observers = $this->eventConfig->getObservers('sales_quote_add_item');

        $this->assertArrayHasKey('synerise_checkout_cart_add_product_complete', $observers);
        $expectedClass = \Synerise\Integration\Observer\CartAddProduct::class;
        $this->assertSame($expectedClass, $observers['synerise_checkout_cart_add_product_complete']['instance']);
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testAddSimpleProduct(): void
    {
        $qty = 2;

        $product = $this->productRepository->getById(1);

        $productHelper = $this->objectManager->get(\Magento\Catalog\Helper\Product::class);
        $isSkipSaleableCheck = $productHelper->getSkipSaleableCheck();
        $productHelper->setSkipSaleableCheck(true);
        $quote = $this->quoteFactory->create();
        $quoteItem = $quote->addProduct($product, $qty);

        $productHelper->setSkipSaleableCheck($isSkipSaleableCheck);

        $uuid = (string) Uuid::Uuid4();

        $request = $this->cartHelper->prepareAddToCartRequest(
            $quoteItem,
            CartAddProduct::EVENT,
            $uuid
        );

        $this->assertTrue($request->valid());
        $this->assertEquals(self::EVENT_LABEL, $request->getLabel());

        $client = $request->getClient();
        $this->assertEquals($client->getUuid(), $uuid);
        $this->assertNull($client->getCustomId());
        $this->assertNull($client->getEmail());

        $params = $request->getParams();
        $this->assertEquals($product->getSku(), $params['sku']);
        $this->assertEquals($product->getName(), $params['name']);
        $this->assertEquals($qty, $params['quantity']);
    }

    /**
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testAddConfigurableProduct(): void
    {
        $sku = 'configurable';
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        /** @var ProductInterface $product */
        $product = $productRepository->get($sku, true, null, true);

        /** @var $options \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection */
        $options = $this->objectManager->create(
            \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection::class
        );

        /** @var \Magento\Eav\Model\Config $eavConfig */
        $eavConfig = $this->objectManager->get(\Magento\Eav\Model\Config::class);
        $attribute = $eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'test_configurable');
        $option = $options->setAttributeFilter($attribute->getId())->getFirstItem();

        $requestInfo = new \Magento\Framework\DataObject(
            [
                'product' => 1,
                'selected_configurable_option' => 1,
                'qty' => 1,
                'super_attribute' => [
                    $attribute->getId() => $option->getId()
                ]
            ]
        );

        $quote = $this->quoteFactory->create();
        $quoteItem = $quote->addProduct($product, $requestInfo);

        $uuid = (string) Uuid::Uuid4();

        $request = $this->cartHelper->prepareAddToCartRequest(
            $quoteItem,
            CartAddProduct::EVENT,
            $uuid
        );

        $this->assertTrue($request->valid());
        $this->assertEquals(self::EVENT_LABEL, $request->getLabel());

        $client = $request->getClient();
        $this->assertEquals($client->getUuid(), $uuid);
        $this->assertNull($client->getCustomId());
        $this->assertNull($client->getEmail());

        $params = $request->getParams();
        $this->assertEquals('simple_10', $params['skuVariant']);
        $this->assertEquals($sku, $params['sku']);
        $this->assertEquals($product->getName(), $params['name']);
        $this->assertEquals(1.0, $params['quantity']);
    }
}