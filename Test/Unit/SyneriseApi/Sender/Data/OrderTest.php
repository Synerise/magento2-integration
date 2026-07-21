<?php

namespace Synerise\Integration\Test\Unit\SyneriseApi\Sender\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\Order as OrderModel;
use PHPUnit\Framework\TestCase;
use Synerise\ApiClient\Model\CreateatransactionRequest;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Order\UuidEmailResolver;
use Synerise\Integration\Helper\Tracking\UuidGenerator;
use Synerise\Integration\Model\Workspace\ConfigFactory as WorkspaceConfigFactory;
use Synerise\Integration\SyneriseApi\ConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory;
use Synerise\Integration\SyneriseApi\Mapper\Data\OrderCRUD;
use Synerise\Integration\SyneriseApi\Sender\Data\Order;

class OrderTest extends TestCase
{
    public function testSendItemsDerivesUuidFromUuidEmailResolver(): void
    {
        // Registered customer: uuid keyed to the live account email, not the order snapshot.
        // The sender passes the order's customer_email as the fallback; UuidEmailResolver
        // returns the live account email here.
        $uuidEmailResolver = $this->createMock(UuidEmailResolver::class);
        $uuidEmailResolver->method('resolve')->willReturn('account@customer.example');

        $uuidGenerator = $this->createMock(UuidGenerator::class);
        $uuidGenerator->method('generateByEmail')
            ->willReturnCallback(fn (string $email): string => 'uuid-of-' . $email);

        $orderCRUD = $this->createMock(OrderCRUD::class);
        $orderCRUD->expects($this->once())
            ->method('prepareRequest')
            ->with($this->anything(), 'uuid-of-account@customer.example', $this->anything())
            ->willReturn($this->createMock(CreateatransactionRequest::class));

        $sender = $this->getMockBuilder(Order::class)
            ->setConstructorArgs([
                $this->createMock(ResourceConnection::class),
                $this->createMock(ConfigFactory::class),
                $this->createMock(InstanceFactory::class),
                $this->createMock(WorkspaceConfigFactory::class),
                $this->createMock(Logger::class),
                $orderCRUD,
                $uuidGenerator,
                $uuidEmailResolver,
            ])
            ->onlyMethods(['batchAddOrUpdateTransactions', 'markItemsAsSent'])
            ->getMock();

        $order = $this->createMock(OrderModel::class);
        $order->method('getEntityId')->willReturn(1);
        $order->method('getCustomerEmail')->willReturn('buyer@customer.example');

        $sender->sendItems([$order], 1);
    }
}
