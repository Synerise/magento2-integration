<?php

namespace Synerise\Integration\Test\Integration\SyneriseApi\Mapper\Event;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Quote\Model\GetQuoteByReservedOrderId;
use Ramsey\Uuid\Uuid;
use Synerise\ApiClient\Model\Client;
use Synerise\ApiClient\Model\CustomeventRequest;
use Synerise\Integration\SyneriseApi\Mapper\Event\CartStatus;

class CartStatusTest extends \PHPUnit\Framework\TestCase
{
    const ACTION = 'cart.status';

    const LABEL = 'CartStatus';

    const STORE_ID = 1;

    const PRODUCT_QTY = 1.0;

    const TOTAL_AMOUNT = 10;

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
     * @var CartStatus
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
        $this->mapper = $this->objectManager->create(CartStatus::class);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     */
    public function testGuestCartStatusWithSimpleProduct(): void
    {
        $product = $this->productRepository->get('simple', false, null, true);
        $quote = $this->getQuoteByReservedOrderId->execute('test01');

        $uuid = (string) Uuid::Uuid4();

        $request = $this->mapper->prepareRequest($quote, $uuid);
        $client = $request->getClient();

        $this->assertTrue($request->valid());
        $this->assertInstanceOf(CustomeventRequest::class, $request);

        $this->assertEquals(self::ACTION, $request->getAction());
        $this->assertEquals(self::LABEL, $request->getLabel());

        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals($uuid, $client->getUuid());
        $this->assertNull($client->getEmail());
        $this->assertNull($client->getId());
        $this->assertNull($client->getCustomId());

        $params = $request->getParams();

        $this->assertEquals(1, count($params['products']));
        foreach($params['products'] as $requestProduct) {
            $this->assertEquals($product->getSku(), $requestProduct['sku']);
            $this->assertEquals(self::PRODUCT_QTY, $requestProduct['quantity']);
            $this->assertFalse(isset($requestProduct['skuVariant']));
        }

        $this->assertEquals(self::TOTAL_AMOUNT, $params['totalAmount']);
    }

    /**
     * @magentoDataFixture Magento/ConfigurableProduct/_files/quote_with_configurable_product.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testCustomerCartStatusWithConfigurableProduct(): void
    {
        $product = $this->productRepository->get('configurable', false, null, true);
        $quote = $this->getQuoteByReservedOrderId->execute('test_cart_with_configurable');

        $customer = $this->customerRepository->getById(1);
        $quote->setCustomer($customer);

        // should be ignored in this test
        $uuid = (string) Uuid::Uuid4();

        $request = $this->mapper->prepareRequest($quote, $uuid);
        $client = $request->getClient();

        $this->assertTrue($request->valid());
        $this->assertInstanceOf(CustomeventRequest::class, $request);

        $this->assertEquals(self::ACTION, $request->getAction());
        $this->assertEquals(self::LABEL, $request->getLabel());

        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals('f95a6e19-158c-5dfa-a4de-5b77fbb55edd', $client->getUuid());
        $this->assertEquals('customer@example.com', $client->getEmail());
        $this->assertNull($client->getId());
        $this->assertEquals(1, $client->getCustomId());

        $params = $request->getParams();

        $this->assertEquals(1, count($params['products']));
        foreach($params['products'] as $requestProduct) {
            $this->assertEquals($product->getSku(), $requestProduct['sku']);
            $this->assertEquals(self::PRODUCT_QTY, $requestProduct['quantity']);
            $this->assertEquals('simple_10', $requestProduct['skuVariant']);
        }

        $this->assertEquals(self::TOTAL_AMOUNT, $params['totalAmount']);
    }

}