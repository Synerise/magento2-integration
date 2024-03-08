<?php

namespace Synerise\Integration\Test\Integration\SyneriseApi\Mapper\Data;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Ramsey\Uuid\Uuid;
use Synerise\Integration\SyneriseApi\Mapper\Data\CustomerCRUD;

class CustomerCRUDTest extends \PHPUnit\Framework\TestCase
{
    const FIXTURE_CUSTOMER_ID = 1;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var CustomerCRUD
     */
    private $mapper;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->mapper = $this->objectManager->create(CustomerCRUD::class);
        $this->addressRepository = $this->objectManager->create(AddressRepositoryInterface::class);
        $this->customerRepository = $this->objectManager->create(CustomerRepositoryInterface::class);
    }

    /**
     * @magentoConfigFixture current_store synerise/customer/attributes dob,gender,default_billing
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Customer/_files/customer_address.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testPrepareRequest(): void
    {
        $customer = $this->customerRepository->getById(self::FIXTURE_CUSTOMER_ID);

        $request = $this->mapper->prepareRequest(
            $customer,
            $customer->getStoreId()
        );

        $this->assertTrue($request->valid());

        $this->assertNull($request->getUuid());
        $this->assertEquals($customer->getId(), $request->getCustomId());
        $this->assertEquals($customer->getEmail(), $request->getEmail());
        $this->assertEquals($customer->getFirstname(), $request->getFirstname());
        $this->assertEquals($customer->getLastname(), $request->getLastName());
        $this->assertNull($request->getSex());

        $defaultAddress = $this->addressRepository->getById($customer->getDefaultBilling());
        $this->assertEquals($defaultAddress->getTelephone(), $request->getPhone());
        $this->assertEquals($defaultAddress->getCity(), $request->getCity());
        $this->assertEquals(implode(" ", $defaultAddress->getStreet()), $request->getAddress());
        $this->assertEquals($defaultAddress->getPostcode(), $request->getZipCode());
        $this->assertEquals($defaultAddress->getRegion()->getRegion(), $request->getProvince());
        $this->assertEquals($defaultAddress->getCountryId(), $request->getCountryCode());
        $this->assertEquals($defaultAddress->getCompany(), $request->getCompany());
    }
}