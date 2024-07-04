<?php

namespace Synerise\Integration\Test\Integration\SyneriseApi\Mapper\Event;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Quote\Model\GetQuoteByReservedOrderId;
use Ramsey\Uuid\Uuid;
use Synerise\ApiClient\Model\Client;
use Synerise\ApiClient\Model\ClientaddedproducttocartRequest;
use Synerise\Integration\Observer\Event\CartAddProduct;
use Synerise\Integration\Observer\Event\CartRemoveProduct;
use Synerise\Integration\SyneriseApi\Mapper\Event\CartAddRemove;

class CartAddRemoveTest extends \PHPUnit\Framework\TestCase
{
    const ADD_LABEL = 'Customer added product to cart';

    const REMOVE_LABEL = 'Customer removed product from cart';

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var GetQuoteByReservedOrderId
     */
    private $getQuoteByReservedOrderId;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var CartAddRemove
     */
    private $mapper;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->getQuoteByReservedOrderId = $this->objectManager->get(GetQuoteByReservedOrderId::class);
        $this->customerRepository = $this->objectManager->create(CustomerRepositoryInterface::class);

        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->mapper = $this->objectManager->create(CartAddRemove::class);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/ConfigurableProduct/_files/quote_with_configurable_product.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testPrepareAddToCartRequestWithConfigurableProduct(): void
    {
        $product = $this->productRepository->get('configurable', false, null, true);
        $quote = $this->getQuoteByReservedOrderId->execute('test_cart_with_configurable');

        $customer = $this->customerRepository->getById(1);
        $quote->setCustomer($customer);

        // should be ignored in this test
        $uuid = (string)Uuid::Uuid4();

        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            $quoteItem->getProduct()->setQty($quoteItem->getQty());
            $request = $this->mapper->prepareRequest(
                CartAddProduct::EVENT,
                $quoteItem,
                $uuid
            );

            $this->assertTrue($request->valid());
            $this->assertInstanceOf(ClientaddedproducttocartRequest::class, $request);
            $this->assertEquals(self::ADD_LABEL, $request->getLabel());

            $client = $request->getClient();
            $this->assertInstanceOf(Client::class, $client);
            $this->assertEquals('f95a6e19-158c-5dfa-a4de-5b77fbb55edd', $client->getUuid());
            $this->assertEquals('customer@example.com', $client->getEmail());
            $this->assertNull($client->getId());
            $this->assertEquals(1, $client->getCustomId());

            $params = $request->getParams();
            $this->assertEquals($product->getSku(), $params['sku']);
            $this->assertEquals($product->getName(), $params['name']);
            $this->assertEquals('simple_10', $params['skuVariant']);
            $this->assertEquals($product->getUrlInStore(), $params['productUrl']);
            $this->assertEquals(1.0, $params['quantity']);

            $finalUnitPrice = $params['finalUnitPrice'];
            $this->assertEquals(10, $finalUnitPrice['amount']);
            $this->assertEquals('USD', $finalUnitPrice['currency']);

            $regularUnitPrice = $params['finalUnitPrice'];
            $this->assertEquals(10, $regularUnitPrice['amount']);
            $this->assertEquals('USD', $regularUnitPrice['currency']);
        }
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote.php
     */
    public function testPrepareAddToCartRequestWithSimpleProduct(): void
    {
        $product = $this->productRepository->get('simple', false, null, true);
        $quote = $this->getQuoteByReservedOrderId->execute('test01');

        $uuid = (string)Uuid::Uuid4();

        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            $quoteItem->getProduct()->setQty($quoteItem->getQty());
            $request = $this->mapper->prepareRequest(
                CartRemoveProduct::EVENT,
                $quoteItem,
                $uuid
            );

            $this->assertTrue($request->valid());
            $this->assertInstanceOf(ClientaddedproducttocartRequest::class, $request);
            $this->assertEquals(self::REMOVE_LABEL, $request->getLabel());

            $client = $request->getClient();
            $this->assertInstanceOf(Client::class, $client);
            $this->assertEquals($uuid, $client->getUuid());
            $this->assertNull($client->getEmail());
            $this->assertNull($client->getId());
            $this->assertNull($client->getCustomId());

            $params = $request->getParams();
            $this->assertEquals($product->getSku(), $params['sku']);
            $this->assertEquals($product->getName(), $params['name']);
            $this->assertFalse(isset($params['skuVariant']));
            $this->assertEquals($product->getUrlInStore(), $params['productUrl']);
            $this->assertEquals(1.0, $params['quantity']);

            $finalUnitPrice = $params['finalUnitPrice'];
            $this->assertEquals(10, $finalUnitPrice['amount']);
            $this->assertEquals('USD', $finalUnitPrice['currency']);

            $regularUnitPrice = $params['finalUnitPrice'];
            $this->assertEquals(10, $regularUnitPrice['amount']);
            $this->assertEquals('USD', $regularUnitPrice['currency']);
        }
    }
}
