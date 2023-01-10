<?php

namespace Synerise\Integration\Test\Integration\Observer;


use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Event\Config;
use Magento\TestFramework\Helper\Bootstrap;
use Ramsey\Uuid\Uuid;
use Synerise\Integration\Helper\Event\Client;
use Synerise\Integration\Observer\CustomerRegister;

class CustomerRegisterObserverTest extends \PHPUnit\Framework\TestCase
{
    const FIXTURE_CUSTOMER_ID = 1;

    const EVENT_LABEL = 'Customer registration';

    /**
     * @var Config $frameworkEvent
     */
    private $eventConfig;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var Client
     */
    private $clientAction;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->eventConfig = $this->objectManager->create(Config::class);
        $this->customerRepository = $this->objectManager->create(CustomerRepositoryInterface::class);

        $this->clientAction = $this->objectManager->get(Client::class);
    }

    public function testObserverRegistration()
    {
        $observers = $this->eventConfig->getObservers('customer_register_success');

        $this->assertArrayHasKey('synerise_customer_register_observer', $observers);
        $expectedClass = CustomerRegister::class;
        $this->assertSame($expectedClass, $observers['synerise_customer_register_observer']['instance']);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testCustomerRegister()
    {
        $customer = $this->customerRepository->getById(self::FIXTURE_CUSTOMER_ID);
        $uuid = (string) Uuid::Uuid4();

        $request = $this->clientAction->prepareEventClientActionRequest(
            CustomerRegister::EVENT,
            $customer,
            $uuid
        );

        $this->assertTrue($request->valid());
        $this->assertEquals(self::EVENT_LABEL, $request->getLabel());

        $client = $request->getClient();
        $this->assertEquals($uuid, $client->getUuid());
        $this->assertEquals($customer->getId(), $client->getCustomId());
        $this->assertEquals($customer->getEmail(), $client->getEmail());
    }
}