<?php

namespace Synerise\Integration\Test\Integration\Helper\Api\Event;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\ProductRepository;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection;
use Magento\Quote\Model\QuoteFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Ramsey\Uuid\Uuid;
use Synerise\Integration\Helper\Api\Event\Cart as CartHelper;
use Synerise\Integration\Observer\Event\Cart\AddProduct;

class CartTest extends \PHPUnit\Framework\TestCase
{
    const CART_ADD_LABEL = 'Customer added product to cart';

    const CART_STATUS_LABEL = 'CartStatus';

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

    /**
     * @var Collection
     */
    private $options;

    /**
     * @var mixed
     */
    private $eavConfig;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->productRepository = $this->objectManager->get(ProductRepository::class);
        $this->quoteFactory = $this->objectManager->get(QuoteFactory::class);
        $this->options = $this->objectManager->create(Collection::class);
        $this->eavConfig = $this->objectManager->get(EavConfig::class);
        $this->cartHelper = $this->objectManager->get(CartHelper::class);
        $this->productHelper = $this->objectManager->get(ProductHelper::class);
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

        $request = $this->cartHelper->prepareAddToCartRequest(
            $quoteItem,
            AddProduct::EVENT,
            $uuid
        );

        $this->assertTrue($request->valid());
        $this->assertEquals(self::CART_ADD_LABEL, $request->getLabel());

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
    public function testPrepareAddToCartRequestConfigurableProduct(): void
    {
        $sku = 'configurable';

        $product = $this->productRepository->get('configurable', true, null, true);

        $attribute = $this->eavConfig->getAttribute(ProductModel::ENTITY, 'test_configurable');
        $option = $this->options->setAttributeFilter($attribute->getId())->getFirstItem();

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
            AddProduct::EVENT,
            $uuid
        );

        $this->assertTrue($request->valid());
        $this->assertEquals(self::CART_ADD_LABEL, $request->getLabel());

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
        $this->assertEquals(self::CART_STATUS_LABEL, $request->getLabel());

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
        $this->assertEquals(self::CART_STATUS_LABEL, $request->getLabel());

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