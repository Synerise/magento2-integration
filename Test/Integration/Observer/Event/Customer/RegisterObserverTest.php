<?php

namespace Synerise\Integration\Test\Integration\Observer;


use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Event\Config;
use Magento\TestFramework\Helper\Bootstrap;
use Ramsey\Uuid\Uuid;
use Synerise\Integration\Helper\Api\Event\Client;
use Synerise\Integration\Helper\Event;
use Synerise\Integration\Observer\Event\Customer\Register;

class RegisterObserverTest extends \PHPUnit\Framework\TestCase
{
    const FIXTURE_CUSTOMER_ID = 1;

    const EVENT_LABEL = 'Customer registration';

    /**
     * @var Config
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

    /**
     * @var Event
     */
    private $event;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->eventConfig = $this->objectManager->create(Config::class);
        $this->customerRepository = $this->objectManager->create(CustomerRepositoryInterface::class);

        $this->clientAction = $this->objectManager->get(Client::class);
        $this->event = $this->objectManager->create(Event::class);
    }

    public function testObserverRegistration()
    {
        $observers = $this->eventConfig->getObservers('customer_register_success');

        $this->assertArrayHasKey('synerise_customer_register_observer', $observers);
        $expectedClass = Register::class;
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
            Register::EVENT,
            $customer,
            $uuid
        );

        $this->assertTrue($request->valid());
        $this->assertEquals(self::EVENT_LABEL, $request->getLabel());

        $client = $request->getClient();
        $this->assertEquals($uuid, $client->getUuid());
        $this->assertEquals($customer->getId(), $client->getCustomId());
        $this->assertEquals($customer->getEmail(), $client->getEmail());

        list ($body, $statusCode, $headers) = $this->event->sendEvent(
            Register::EVENT,
            $request,
            $customer->getStoreId(),
            $customer->getId()
        );

        $this->assertEquals(202, $statusCode);
    }
}