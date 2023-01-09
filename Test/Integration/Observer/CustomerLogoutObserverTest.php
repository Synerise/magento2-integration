<?php

namespace Synerise\Integration\Test\Integration\Observer;


use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Ramsey\Uuid\Uuid;
use Synerise\Integration\Observer\CustomerLogout;

class CustomerLogoutObserverTest extends \PHPUnit\Framework\TestCase
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

    const EVENT_LABEL = 'Customer logout';

    /**
     * @var \Synerise\Integration\Helper\Event\Client
     */
    private $clientAction;

    /**
     * @var \Synerise\Integration\Helper\Update\Client
     */
    private $clientUpdate;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->eventConfig = $this->objectManager->create(\Magento\Framework\Event\Config::class);
        $this->customerRepository = $this->objectManager->create(
            \Magento\Customer\Api\CustomerRepositoryInterface::class
        );

        $this->clientAction = $this->objectManager->get(\Synerise\Integration\Helper\Event\Client::class);
        $this->clientUpdate = $this->objectManager->get(\Synerise\Integration\Helper\Update\Client::class);
    }

    /**
     * @return void
     */
    public function testObserverRegistration()
    {
        $observers = $this->eventConfig->getObservers('customer_logout');

        $this->assertArrayHasKey('synerise_customer_logout_observer', $observers);
        $expectedClass = CustomerLogout::class;
        $this->assertSame($expectedClass, $observers['synerise_customer_logout_observer']['instance']);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testCustomerLogout()
    {
        $customer = $this->customerRepository->getById(self::FIXTURE_CUSTOMER_ID);
        $uuid = (string) Uuid::Uuid4();

        $request = $this->clientAction->prepareEventClientActionRequest(
            CustomerLogout::EVENT,
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