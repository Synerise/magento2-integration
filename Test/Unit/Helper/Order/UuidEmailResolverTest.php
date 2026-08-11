<?php

namespace Synerise\Integration\Test\Unit\Helper\Order;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\TestCase;
use Synerise\Integration\Helper\Order\UuidEmailResolver;

class UuidEmailResolverTest extends TestCase
{
    public function testUsesLiveAccountEmailForRegisteredCustomer(): void
    {
        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getEmail')->willReturn('current@customer.example');

        $repo = $this->createMock(CustomerRepositoryInterface::class);
        $repo->expects($this->once())->method('getById')->with(42)->willReturn($customer);

        $resolver = new UuidEmailResolver($repo);

        $order = $this->createMock(Order::class);
        $order->method('getCustomerId')->willReturn(42);

        // Even though a stale snapshot is passed as fallback, the live account email wins.
        $this->assertSame(
            'current@customer.example',
            $resolver->resolve($order, 'stale-snapshot@customer.example')
        );
    }

    public function testFallsBackToFallbackEmailForGuest(): void
    {
        $repo = $this->createMock(CustomerRepositoryInterface::class);
        $repo->expects($this->never())->method('getById');

        $resolver = new UuidEmailResolver($repo);

        $order = $this->createMock(Order::class);
        $order->method('getCustomerId')->willReturn(null);

        $this->assertSame('guest@customer.example', $resolver->resolve($order, 'guest@customer.example'));
    }

    public function testFallsBackWhenCustomerMissing(): void
    {
        $repo = $this->createMock(CustomerRepositoryInterface::class);
        $repo->method('getById')->willThrowException(new NoSuchEntityException());

        $resolver = new UuidEmailResolver($repo);

        $order = $this->createMock(Order::class);
        $order->method('getCustomerId')->willReturn(99);

        $this->assertSame('fallback@customer.example', $resolver->resolve($order, 'fallback@customer.example'));
    }

    public function testReturnsNullWhenGuestFallbackNull(): void
    {
        $repo = $this->createMock(CustomerRepositoryInterface::class);

        $resolver = new UuidEmailResolver($repo);

        $order = $this->createMock(Order::class);
        $order->method('getCustomerId')->willReturn(null);

        $this->assertNull($resolver->resolve($order, null));
    }
}
