<?php

namespace Synerise\Integration\Test\Integration\SyneriseApi\Mapper\Data;

use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;
use Ramsey\Uuid\Uuid;
use Synerise\ApiClient\Model\CreateatransactionRequest;
use Synerise\Integration\Helper\Tracking\Context;
use Synerise\Integration\Helper\Tracking\UuidGenerator;
use Synerise\Integration\SyneriseApi\Mapper\Data\OrderCRUD;

class OrderCRUDTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var OrderCRUD
     */
    private $mapper;

    /**
     * @var Context
     */
    private $contextHelper;

    /**
     * @var UuidGenerator
     */
    private $uuidGenerator;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->mapper = $this->objectManager->create(OrderCRUD::class);
        $this->contextHelper = $this->objectManager->create(Context::class);
        $this->uuidGenerator = $this->objectManager->create(UuidGenerator::class);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testPrepareRequest(): void
    {
        $order = $this->loadOrderByIncrementId('100000001');
        $uuid = $this->uuidGenerator->generateByEmail($order->getCustomerEmail());

        $request = $this->mapper->prepareRequest(
            $order,
            $uuid
        );

        $this->assertTrue($request->valid());
        $this->assertInstanceOf(CreateatransactionRequest::class, $request);
        $this->assertEquals($order->getRealOrderId(), $request->getOrderId());
        $this->assertEquals($order->getRealOrderId(), $request->getEventSalt());
        $this->assertEquals(
            $this->contextHelper->formatDateTimeAsIso8601(new \DateTime($order->getCreatedAt())),
            $request->getRecordedAt()
        );
        $this->assertEquals($this->contextHelper->getSource(), $request->getSource());

        $clientData = $request->getClient();
        $this->assertEquals($order->getCustomerEmail(), $clientData->getEmail());
        $this->assertEquals($uuid, $clientData->getUuid());
        $this->assertNull($clientData->getCustomId());

        $this->assertNull($request->getDiscountAmount());

        $metadata = $request->getMetadata();

        $this->assertEquals($order->getStatus(), $metadata['orderStatus']);
        $this->assertEquals($order->getCouponCode(), $metadata['discountCode']);
        $this->assertEquals($order->getShippingMethod(), $metadata['shipping']['method']);
        $this->assertEquals($this->contextHelper->getApplicationName(), $metadata['applicationName']);
        $this->assertEquals($order->getStoreId(), $metadata['storeId']);
        $this->assertEquals($order->getPayment()->getMethod(), $request->getPaymentInfo()->getMethod());
        $this->assertEquals($order->getSubTotal(), $request->getRevenue()->getAmount());
        $this->assertEquals($order->getSubTotal(), $request->getValue()->getAmount());

        $products = $request->getProducts();
        $product = $products[0];
        $this->assertEquals('simple', $product['sku']);
        $this->assertEquals('Simple Product', $product['name']);
        $this->assertEquals(['amount' => 10.0, 'currency' => 'USD'], $product['regularPrice']);
        $this->assertEquals(['amount' => 10.0, 'currency' => 'USD'], $product['finalUnitPrice']);
        $this->assertEquals(2, $product['quantity']);
    }

    protected function loadOrderByIncrementId(string $incrementId): Order
    {
        return $this->objectManager->create(Order::class)->loadByIncrementId($incrementId);
    }
}
