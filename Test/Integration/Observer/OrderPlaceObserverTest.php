<?php

namespace Synerise\Integration\Test\Integration\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Quote\Model\QuoteFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Ramsey\Uuid\Uuid;
use Synerise\Integration\Helper\Data\Context;
use Synerise\Integration\Helper\Update\Transaction as OrderHelper;
use Synerise\Integration\Helper\Identity;
use Synerise\Integration\Observer\CartAddProduct;
use Synerise\Integration\Observer\OrderPlace;

class OrderPlaceObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\Event\Config $frameworkEvent
     */
    private $eventConfig;

    const EVENT_LABEL = 'Customer added product to cart';

    /**
     * @var Context
     */
    private $contextHelper;

    /**
     * @var OrderHelper
     */
    private $orderHelper;

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

        $this->contextHelper = $this->objectManager->get(Context::class);
        $this->orderHelper = $this->objectManager->get(OrderHelper::class);
    }

    public function testObserverRegistration()
    {
        $observers = $this->eventConfig->getObservers('sales_order_save_after');

        $this->assertArrayHasKey('synerise_sales_order_save_after', $observers);
        $expectedClass = OrderPlace::class;
        $this->assertSame($expectedClass, $observers['synerise_sales_order_save_after']['instance']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testPlaceOrder(): void
    {
        $uuid = (string) Uuid::Uuid4();
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->objectManager->create(\Magento\Sales\Model\Order::class)->loadByIncrementId('100000001');
        $address = $order->getShippingAddress();

        $transactionRequest = $this->orderHelper->prepareCreateTransactionRequest($order, $uuid);
        $this->assertTrue($transactionRequest->valid());
        $this->assertEquals($order->getRealOrderId(), $transactionRequest->getOrderId());
        $this->assertEquals($order->getRealOrderId(), $transactionRequest->getEventSalt());
        $this->assertEquals(
            $this->contextHelper->formatDateTimeAsIso8601(new \DateTime($order->getCreatedAt())),
            $transactionRequest->getRecordedAt()
        );
        $this->assertEquals($this->contextHelper->getSource(), $transactionRequest->getSource());

        $clientData = $transactionRequest->getClient();
        $this->assertEquals($order->getCustomerEmail(), $clientData->getEmail());
        $this->assertEquals($uuid, $clientData->getUuid());
        $this->assertNull($clientData->getCustomId());

        $discountAmount = $transactionRequest->getDiscountAmount();
        $this->assertEquals($order->getDiscountAmount(), $discountAmount->getAmount());
        $this->assertEquals($order->getOrderCurrencyCode(), $discountAmount->getCurrency());

        $metadata = $transactionRequest->getMetadata();

        $this->assertEquals($order->getStatus(), $metadata['orderStatus']);
        $this->assertEquals($order->getCouponCode(), $metadata['discountCode']);
        $this->assertEquals($order->getShippingMethod(), $metadata['shipping']['method']);
        $this->assertEquals($this->contextHelper->getApplicationName(), $metadata['applicationName']);
        $this->assertEquals($order->getStoreId(), $metadata['storeId']);
        $this->assertEquals($order->getPayment()->getMethod(), $transactionRequest->getPaymentInfo()->getMethod());
        $this->assertEquals($order->getSubTotal(), $transactionRequest->getRevenue()->getAmount());
        $this->assertEquals($order->getSubTotal(), $transactionRequest->getValue()->getAmount());

        $products = $transactionRequest->getProducts();
        $product = $products[0];
        $this->assertEquals('simple', $product['sku']);
        $this->assertEquals('Simple Product', $product['name']);
        $this->assertEquals(['amount' => 10.0, 'currency' => 'USD'], $product['regularPrice']);
        $this->assertEquals(['amount' => 10.0, 'currency' => 'USD'], $product['finalUnitPrice']);
        $this->assertEquals(2, $product['quantity']);

        $clientRequest = $this->orderHelper->prepareCreateClientRequest($order, $uuid);
        $this->assertTrue($clientRequest->valid());
        $this->assertEquals($order->getCustomerEmail(), $clientRequest->getEmail());
        $this->assertEquals($uuid, $clientRequest->getUuid());
        $this->assertEquals($order->getCustomerFirstname(), $clientRequest->getFirstName());
        $this->assertEquals($order->getCustomerLastname(), $clientRequest->getLastName());
        $this->assertEquals($address->getTelephone(), $clientRequest->getPhone());
    }
}