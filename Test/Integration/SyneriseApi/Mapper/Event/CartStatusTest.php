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

        $response = $this->mapper->prepareRequest($quote, $uuid);
        $client = $response->getClient();

        $this->assertTrue($response->valid());
        $this->assertInstanceOf(CustomeventRequest::class, $response);

        $this->assertEquals(self::ACTION, $response->getAction());
        $this->assertEquals(self::LABEL, $response->getLabel());

        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals($uuid, $client->getUuid());
        $this->assertNull($client->getEmail());
        $this->assertNull($client->getId());
        $this->assertNull($client->getCustomId());

        $params = $response->getParams();

        $this->assertEquals(1, count($params['products']));
        foreach($params['products'] as $responseProduct) {
            $this->assertEquals($product->getSku(), $responseProduct['sku']);
            $this->assertEquals(self::PRODUCT_QTY, $responseProduct['quantity']);
            $this->assertFalse(isset($responseProduct['skuVariant']));
        }

        $this->assertEquals(self::TOTAL_AMOUNT, $params['totalAmount']);
    }

    /**
     * @magentoDataFixture Magento/ConfigurableProduct/_files/quote_with_configurable_product.php
     */
    public function testCustomerCartStatusWithConfigurableProduct(): void
    {
        $product = $this->productRepository->get('configurable', false, null, true);
        $quote = $this->getQuoteByReservedOrderId->execute('test_cart_with_configurable');

        $customer = $this->customerRepository->getById(1);
        $quote->setCustomer($customer);

        // should be ignored in this test
        $uuid = (string) Uuid::Uuid4();

        $response = $this->mapper->prepareRequest($quote, $uuid);
        $client = $response->getClient();

        $this->assertTrue($response->valid());
        $this->assertInstanceOf(CustomeventRequest::class, $response);

        $this->assertEquals(self::ACTION, $response->getAction());
        $this->assertEquals(self::LABEL, $response->getLabel());

        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals('c2f2a1b6-f1b3-51e8-b78a-40c0f35d55c7', $client->getUuid());
        $this->assertEquals('roni_cost@example.com', $client->getEmail());
        $this->assertNull($client->getId());
        $this->assertEquals(1, $client->getCustomId());

        $params = $response->getParams();

        $this->assertEquals(1, count($params['products']));
        foreach($params['products'] as $responseProduct) {
            $this->assertEquals($product->getSku(), $responseProduct['sku']);
            $this->assertEquals(self::PRODUCT_QTY, $responseProduct['quantity']);
            $this->assertEquals('simple_10', $responseProduct['skuVariant']);
        }

        $this->assertEquals(self::TOTAL_AMOUNT, $params['totalAmount']);
    }

}