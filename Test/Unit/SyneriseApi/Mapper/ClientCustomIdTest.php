<?php

namespace Synerise\Integration\Test\Unit\SyneriseApi\Mapper;

use Magento\Customer\Model\Data\Customer as CustomerData;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote;
use Magento\Review\Model\Review;
use Magento\Wishlist\Model\Wishlist;
use PHPUnit\Framework\TestCase;
use Synerise\Integration\Helper\Tracking\UuidGenerator;
use Synerise\Integration\SyneriseApi\Mapper\Data\CustomerCRUD;
use Synerise\Integration\SyneriseApi\Mapper\Event\CartAddRemove;
use Synerise\Integration\SyneriseApi\Mapper\Event\CartStatus;
use Synerise\Integration\SyneriseApi\Mapper\Event\CustomerEvent;
use Synerise\Integration\SyneriseApi\Mapper\Event\ReviewAdd;
use Synerise\Integration\SyneriseApi\Mapper\Event\WishlistAdd;
use Synerise\Integration\SyneriseApi\Mapper\Event\WishlistRemove;

/**
 * Every client builder sends custom_id as a string, whatever type the customer id has in Magento.
 */
class ClientCustomIdTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private ObjectManager $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
    }

    private function quote($customerId, ?string $email = 'customer@example.com'): Quote
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCustomerEmail', 'getCustomerId'])
            ->getMock();
        $quote->method('getCustomerEmail')->willReturn($email);
        $quote->method('getCustomerId')->willReturn($customerId);
        return $quote;
    }

    public function testCartAddRemoveSendsCustomIdAsString(): void
    {
        /** @var CartAddRemove $mapper */
        $mapper = $this->objectManager->getObject(CartAddRemove::class, ['uuidGenerator' => new UuidGenerator()]);

        $this->assertSame('3', $mapper->prepareClientDataFromQuote($this->quote(3))->getCustomId());
        $this->assertSame('3', $mapper->prepareClientDataFromQuote($this->quote('3'))->getCustomId());
    }

    public function testCartAddRemoveGuestHasNoCustomId(): void
    {
        /** @var CartAddRemove $mapper */
        $mapper = $this->objectManager->getObject(CartAddRemove::class, ['uuidGenerator' => new UuidGenerator()]);

        $this->assertNull($mapper->prepareClientDataFromQuote($this->quote(null, null), 'cookie-uuid')->getCustomId());
    }

    public function testCartStatusSendsCustomIdAsString(): void
    {
        /** @var CartStatus $mapper */
        $mapper = $this->objectManager->getObject(CartStatus::class, ['uuidGenerator' => new UuidGenerator()]);

        $this->assertSame('3', $mapper->prepareClientDataFromQuote($this->quote(3))->getCustomId());
    }

    public function testReviewSendsCustomIdAsString(): void
    {
        $review = $this->getMockBuilder(Review::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCustomerId'])
            ->getMock();
        $review->method('getCustomerId')->willReturn(3);
        /** @var ReviewAdd $mapper */
        $mapper = $this->objectManager->getObject(ReviewAdd::class);

        $this->assertSame('3', $mapper->prepareClient($review)->getCustomId());
    }

    public function testWishlistEventsSendCustomIdAsString(): void
    {
        $wishlist = $this->createMock(Wishlist::class);
        $wishlist->method('getCustomerId')->willReturn(3);

        /** @var WishlistAdd $add */
        $add = $this->objectManager->getObject(WishlistAdd::class);
        /** @var WishlistRemove $remove */
        $remove = $this->objectManager->getObject(WishlistRemove::class);

        $this->assertSame('3', $add->prepareClient($wishlist)->getCustomId());
        $this->assertSame('3', $remove->prepareClient($wishlist)->getCustomId());
    }

    public function testCustomerSyncSendsCustomIdAsString(): void
    {
        $customer = $this->createMock(CustomerData::class);
        $customer->method('getId')->willReturn(3);
        $customer->method('getEmail')->willReturn('customer@example.com');
        $customer->method('getFirstname')->willReturn('Ann');
        $customer->method('getLastname')->willReturn('Example');
        $customer->method('__toArray')->willReturn(['id' => 3, 'email' => 'customer@example.com']);
        /** @var CustomerCRUD $mapper */
        $mapper = $this->objectManager->getObject(CustomerCRUD::class);

        $this->assertSame('3', $mapper->prepareRequest($customer, 1)->getCustomId());
    }

    public function testCustomerSyncWithoutIdOmitsCustomId(): void
    {
        $customer = $this->createMock(CustomerData::class);
        $customer->method('getId')->willReturn(null);
        $customer->method('getEmail')->willReturn('customer@example.com');
        $customer->method('__toArray')->willReturn(['email' => 'customer@example.com']);
        /** @var CustomerCRUD $mapper */
        $mapper = $this->objectManager->getObject(CustomerCRUD::class);

        $this->assertNull($mapper->prepareRequest($customer, 1)->getCustomId());
    }
}
