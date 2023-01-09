<?php

namespace Synerise\Integration\Test\Integration\Observer;


use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Ramsey\Uuid\Uuid;
use Synerise\Integration\Helper\Update\Client;
use Synerise\Integration\Observer\CustomerSaveAfter;

class CustomerSaveAfterObserverTest extends \PHPUnit\Framework\TestCase
{
    const FIXTURE_CUSTOMER_ID = 1;
    /**
     * @var \Magento\Framework\Event\Config $frameworkEvent
     */
    private $eventConfig;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

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
        $this->addressRepository = $this->objectManager->create(
            \Magento\Customer\Api\AddressRepositoryInterface::class
        );
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
        $observers = $this->eventConfig->getObservers('customer_save_after');

        $this->assertArrayHasKey('synerise_customer_save_after', $observers);
        $expectedClass = CustomerSaveAfter::class;
        $this->assertSame($expectedClass, $observers['synerise_customer_save_after']['instance']);
    }

    /**
     * @magentoConfigFixture current_store synerise/customer/attributes dob,gender,default_billing
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Customer/_files/customer_address.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testCustomerSaveAfter()
    {
        $customer = $this->customerRepository->getById(self::FIXTURE_CUSTOMER_ID);
        $uuid = (string) Uuid::Uuid4();

        $request = $this->clientUpdate->prepareCreateClientRequest(
            $customer,
            $uuid,
            $customer->getStoreId()
        );

        $this->assertTrue($request->valid());

        $this->assertEquals($uuid, $request->getUuid());
        $this->assertEquals($customer->getId(), $request->getCustomId());
        $this->assertEquals($customer->getEmail(), $request->getEmail());
        $this->assertEquals($customer->getFirstname(), $request->getFirstname());
        $this->assertEquals($customer->getLastname(), $request->getLastName());
        $this->assertNull($request->getSex());

        $defaultAddress = $this->addressRepository->getById($customer->getDefaultBilling());
        $this->assertEquals($defaultAddress->getTelephone(), $request->getPhone());
        $this->assertEquals($defaultAddress->getCity(), $request->getCity());
        $this->assertEquals(implode(" ", $defaultAddress->getStreet()), $request->getAddress());
        $this->assertEquals($defaultAddress->getCity(), $request->getCity());
        $this->assertEquals($defaultAddress->getPostcode(), $request->getZipCode());
        $this->assertEquals($defaultAddress->getRegion()->getRegion(), $request->getProvince());
        $this->assertEquals($defaultAddress->getCountryId(), $request->getCountryCode());
        $this->assertEquals($defaultAddress->getCompany(), $request->getCompany());
    }
}