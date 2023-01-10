<?php

namespace Synerise\Integration\Test\Integration\Observer;


use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Ramsey\Uuid\Uuid;
use Synerise\Integration\Observer\CustomerLogin;

class CustomerLoginObserverTest extends \PHPUnit\Framework\TestCase
{
    const FIXTURE_CUSTOMER_ID = 1;
    /**
     * @var \Magento\Framework\Event\Config $frameworkEvent
     */
    private $eventConfig;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    const EVENT_LABEL = 'Customer login';

    /**
     * @var \Synerise\Integration\Helper\Event\Client
     */
    private $clientAction;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->eventConfig = $this->objectManager->create(\Magento\Framework\Event\Config::class);
        $this->customerRepository = $this->objectManager->create(
            \Magento\Customer\Api\CustomerRepositoryInterface::class
        );

        $this->clientAction = $this->objectManager->get(\Synerise\Integration\Helper\Event\Client::class);
    }

    public function testObserverRegistration()
    {
        $observers = $this->eventConfig->getObservers('customer_login');

        $this->assertArrayHasKey('synerise_customer_login_observer', $observers);
        $expectedClass = CustomerLogin::class;
        $this->assertSame($expectedClass, $observers['synerise_customer_login_observer']['instance']);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testCustomerLogin()
    {
        $customer = $this->customerRepository->getById(self::FIXTURE_CUSTOMER_ID);
        $uuid = (string) Uuid::Uuid4();

        $request = $this->clientAction->prepareEventClientActionRequest(
            CustomerLogin::EVENT,
            $customer,
            $uuid
        );

        $this->assertTrue($request->valid());
        $this->assertEquals(self::EVENT_LABEL, $request->getLabel());

        $client = $request->getClient();
        $this->assertEquals($client->getUuid(), $uuid);
        $this->assertEquals($customer->getId(), $client->getCustomId());
        $this->assertEquals($customer->getEmail(), $client->getEmail());
    }
}