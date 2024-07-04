<?php

namespace Synerise\Integration\Test\Integration\SyneriseApi\Mapper\Event;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Ramsey\Uuid\Uuid;
use Synerise\ApiClient\Model\Client;
use Synerise\ApiClient\Model\EventClientAction;
use Synerise\Integration\Observer\Event\CustomerLogin;
use Synerise\Integration\Observer\Event\CustomerLogout;
use Synerise\Integration\Observer\Event\CustomerRegister;
use Synerise\Integration\SyneriseApi\Mapper\Event\CustomerEvent;

class CustomerEventTest extends \PHPUnit\Framework\TestCase
{
    const FIXTURE_CUSTOMER_ID = 1;

    const ACTION = 'cart.status';

    const LOGIN_LABEL = 'Customer login';

    const LOGOUT_LABEL = 'Customer logout';

    const REGISTER_LABEL = 'Customer registration';

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var CustomerEvent
     */
    private $mapper;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->customerRepository = $this->objectManager->create(CustomerRepositoryInterface::class);

        $this->mapper = $this->objectManager->create(CustomerEvent::class);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testPrepareCustomerLoginRequest(): void
    {
        $customer = $this->customerRepository->getById(self::FIXTURE_CUSTOMER_ID);
        $uuid = (string) Uuid::Uuid4();

        $request = $this->mapper->prepareRequest(
            CustomerLogin::EVENT,
            $customer,
            $uuid
        );

        $this->assertTrue($request->valid());
        $this->assertInstanceOf(EventClientAction::class, $request);

        $this->assertEquals(self::LOGIN_LABEL, $request->getLabel());

        $client = $request->getClient();

        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals($uuid, $client->getUuid());
        $this->assertEquals('customer@example.com', $client->getEmail());
        $this->assertNull($client->getId());
        $this->assertNull($client->getCustomId());
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testPrepareCustomerLogoutRequest(): void
    {
        $customer = $this->customerRepository->getById(self::FIXTURE_CUSTOMER_ID);
        $uuid = (string) Uuid::Uuid4();

        $request = $this->mapper->prepareRequest(
            CustomerLogout::EVENT,
            $customer,
            $uuid
        );

        $this->assertTrue($request->valid());
        $this->assertInstanceOf(EventClientAction::class, $request);

        $this->assertEquals(self::LOGOUT_LABEL, $request->getLabel());

        $client = $request->getClient();

        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals($uuid, $client->getUuid());
        $this->assertEquals('customer@example.com', $client->getEmail());
        $this->assertNull($client->getId());
        $this->assertNull($client->getCustomId());
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testPrepareCustomerRegisterRequest(): void
    {
        $customer = $this->customerRepository->getById(self::FIXTURE_CUSTOMER_ID);
        $uuid = (string) Uuid::Uuid4();

        $request = $this->mapper->prepareRequest(
            CustomerRegister::EVENT,
            $customer,
            $uuid
        );

        $this->assertTrue($request->valid());
        $this->assertInstanceOf(EventClientAction::class, $request);

        $this->assertEquals(self::REGISTER_LABEL, $request->getLabel());

        $client = $request->getClient();

        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals($uuid, $client->getUuid());
        $this->assertEquals('customer@example.com', $client->getEmail());
        $this->assertNull($client->getId());
        $this->assertNull($client->getCustomId());
    }
}
