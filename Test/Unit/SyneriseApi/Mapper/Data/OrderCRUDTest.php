<?php

namespace Synerise\Integration\Test\Unit\SyneriseApi\Mapper\Data;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use PHPUnit\Framework\TestCase;
use Synerise\ApiClient\Model\Client;
use Synerise\ApiClient\Model\CreateatransactionRequestRevenue;
use Synerise\ApiClient\Model\CreateatransactionRequestValue;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Product\Category;
use Synerise\Integration\Helper\Product\Image;
use Synerise\Integration\Helper\Product\Price;
use Synerise\Integration\Helper\Tracking\Context;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\SyneriseApi\Mapper\Data\OrderCRUD;

class OrderCRUDTest extends TestCase
{
    private function ctorArgs(ManagerInterface $eventManager, DataObjectFactory $dataObjectFactory): array
    {
        return [
            $this->createMock(SearchCriteriaBuilder::class),
            $this->createMock(RuleRepositoryInterface::class),
            $this->createMock(Category::class),
            $this->createMock(Context::class),
            $this->createMock(Cookie::class),
            $this->createMock(Image::class),
            $this->createMock(Logger::class),
            $this->createMock(Price::class),
            $eventManager,
            $dataObjectFactory,
        ];
    }

    private function dataObjectFactory(): DataObjectFactory
    {
        $factory = $this->createMock(DataObjectFactory::class);
        $factory->method('create')
            ->willReturnCallback(fn (array $args = []): DataObject => new DataObject($args['data'] ?? []));
        return $factory;
    }

    private function eventManager(?callable $onDispatch = null): ManagerInterface
    {
        $eventManager = $this->createMock(ManagerInterface::class);
        $eventManager->method('dispatch')->willReturnCallback(
            function (string $name, array $data = []) use ($onDispatch): void {
                if ($onDispatch !== null) {
                    $onDispatch($name, $data);
                }
            }
        );
        return $eventManager;
    }

    private function plainMapper(): OrderCRUD
    {
        return new OrderCRUD(...$this->ctorArgs($this->eventManager(), $this->dataObjectFactory()));
    }

    // ---- prepareClient (no event; identity assembly only) --------------------------------

    public function testPrepareClientBuildsEmailAndUuid(): void
    {
        $shipping = $this->createMock(Address::class);
        $shipping->method('getTelephone')->willReturn('+48123456789');
        $order = $this->createMock(Order::class);
        $order->method('getCustomerEmail')->willReturn('buyer@customer.example');
        $order->method('getShippingAddress')->willReturn($shipping);

        $client = $this->plainMapper()->prepareClient($order, 'uuid-123');

        $this->assertSame('buyer@customer.example', $client->getEmail());
        $this->assertSame('uuid-123', $client->getUuid());
    }

    public function testPrepareClientNeverSetsCustomId(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getCustomerEmail')->willReturn('buyer@customer.example');
        $order->method('getShippingAddress')->willReturn(null);

        $client = $this->plainMapper()->prepareClient($order, 'uuid-123');

        $this->assertNull($client->getCustomId());
    }

    // ---- prepareRequest (dispatches the whole-request event) -----------------------------

    /**
     * Partial-mock the heavy param builders so the test targets prepareRequest's own
     * assembly + event dispatch. $onDispatch simulates an observer mutating the transport.
     */
    private function requestMapper(?callable $onDispatch, Client $client): OrderCRUD
    {
        $mapper = $this->getMockBuilder(OrderCRUD::class)
            ->setConstructorArgs($this->ctorArgs($this->eventManager($onDispatch), $this->dataObjectFactory()))
            ->onlyMethods([
                'prepareClient',
                'prepareMetadata',
                'preparePaymentInfo',
                'prepareRevenue',
                'prepareValue',
                'getOrderSubtotal',
            ])
            ->getMock();

        $mapper->method('prepareClient')->willReturn($client);
        $mapper->method('prepareMetadata')->willReturn(['orderStatus' => 'pending']);
        $mapper->method('preparePaymentInfo')->willReturn(null);
        $mapper->method('prepareRevenue')->willReturn($this->createMock(CreateatransactionRequestRevenue::class));
        $mapper->method('prepareValue')->willReturn($this->createMock(CreateatransactionRequestValue::class));
        $mapper->method('getOrderSubtotal')->willReturn(0.0);

        return $mapper;
    }

    private function requestOrder(): Order
    {
        $order = $this->createMock(Order::class);
        $order->method('getAllItems')->willReturn([]);
        $order->method('getRealOrderId')->willReturn('100000001');
        $order->method('getOrderCurrencyCode')->willReturn('USD');
        $order->method('getCreatedAt')->willReturn(null);
        $order->method('getSubTotal')->willReturn(0);
        $order->method('getDiscountAmount')->willReturn(0);
        $order->method('getStoreId')->willReturn(1);
        return $order;
    }

    public function testPrepareRequestDispatchesEventWithParamsTransport(): void
    {
        $captured = [];
        $order = $this->requestOrder();
        $client = new Client(['email' => 'buyer@customer.example', 'uuid' => 'u1']);

        $this->requestMapper(function (string $name, array $data) use (&$captured): void {
            $captured = ['name' => $name, 'data' => $data];
        }, $client)->prepareRequest($order, 'u1', ['type' => null]);

        $this->assertSame('synerise_order_request_prepare', $captured['name']);
        $this->assertSame($order, $captured['data']['order']);
        $this->assertInstanceOf(DataObject::class, $captured['data']['transport']);
        // The transport carries the full request params, incl. the client object.
        $this->assertSame($client, $captured['data']['transport']->getData('client'));
        $this->assertSame('100000001', $captured['data']['transport']->getData('order_id'));
    }

    public function testObserverCanOverrideClientIdentityViaEvent(): void
    {
        $client = new Client(['email' => 'buyer@customer.example', 'uuid' => 'u1']);

        $request = $this->requestMapper(function (string $name, array $data): void {
            // Identity lives on the client object; mutate via its setters, keeping uuid in sync.
            $data['transport']->getData('client')
                ->setEmail('observer@customer.example')
                ->setUuid('observer-uuid');
        }, $client)->prepareRequest($this->requestOrder(), 'u1', ['type' => null]);

        $this->assertSame('observer@customer.example', $request->getClient()->getEmail());
        $this->assertSame('observer-uuid', $request->getClient()->getUuid());
    }

    public function testObserverCanEnrichMetadataViaEvent(): void
    {
        $client = new Client(['email' => 'buyer@customer.example', 'uuid' => 'u1']);

        $request = $this->requestMapper(function (string $name, array $data): void {
            $metadata = $data['transport']->getData('metadata');
            $metadata['customField'] = 'enriched';
            $data['transport']->setData('metadata', $metadata);
        }, $client)->prepareRequest($this->requestOrder(), 'u1', ['type' => null]);

        $this->assertSame('enriched', $request->getMetadata()['customField']);
    }
}
