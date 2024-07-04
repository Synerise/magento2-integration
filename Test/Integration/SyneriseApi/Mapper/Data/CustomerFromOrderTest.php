<?php

namespace Synerise\Integration\Test\Integration\SyneriseApi\Mapper\Data;

use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;
use Ramsey\Uuid\Uuid;
use Synerise\Integration\SyneriseApi\Mapper\Data\CustomerFromOrder;

class CustomerFromOrderTest extends \PHPUnit\Framework\TestCase
{
    const FIXTURE_CUSTOMER_ID = 1;
    const CUSTOMER_EMAIl = 'customer@example.com';
    const CUSTOMER_FIRSTNAME = 'John';
    const CUSTOMER_LASTNAME = 'Doe';

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var CustomerFromOrder
     */
    private $mapper;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->mapper = $this->objectManager->create(CustomerFromOrder::class);
    }

    /**
     * @magentoConfigFixture current_store synerise/customer/attributes dob,gender,default_billing
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testPrepareRequest(): void
    {
        $order = $this->loadOrderByIncrementId('100000001');
        $order->setCustomerEmail(self::CUSTOMER_EMAIl);
        $order->setCustomerFirstname(self::CUSTOMER_FIRSTNAME);
        $order->setCustomerLastname(self::CUSTOMER_LASTNAME);

        $uuid = (string) Uuid::Uuid4();
        $request = $this->mapper->prepareRequest(
            $order,
            $uuid
        );

        $this->assertTrue($request->valid());

        $this->assertEquals($uuid, $request->getUuid());
        $this->assertNull($request->getCustomId());
        $this->assertEquals(self::CUSTOMER_EMAIl, $request->getEmail());
        $this->assertEquals(self::CUSTOMER_FIRSTNAME, $request->getFirstname());
        $this->assertEquals(self::CUSTOMER_LASTNAME, $request->getLastName());
        $this->assertEquals($order->getShippingAddress()->getTelephone(), $request->getPhone());
        $this->assertNull($request->getSex());
    }

    /**
     * @param string $incrementId
     * @return Order
     */
    protected function loadOrderByIncrementId(string $incrementId): Order
    {
        return $this->objectManager->create(Order::class)->loadByIncrementId($incrementId);
    }
}
