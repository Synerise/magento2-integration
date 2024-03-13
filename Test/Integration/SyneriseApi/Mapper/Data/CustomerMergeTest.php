<?php

namespace Synerise\Integration\Test\Integration\SyneriseApi\Mapper\Data;

use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Ramsey\Uuid\Uuid;
use Synerise\Integration\SyneriseApi\Mapper\Data\CustomerMerge;

class CustomerMergeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var CustomerMerge
     */
    private $mapper;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->mapper = $this->objectManager->create(CustomerMerge::class);
    }

    public function testPrepareRequest(): void
    {
       $email = 'customer@example.com';
       $prevUuid = (string) Uuid::Uuid4();
       $currUuid = (string) Uuid::Uuid4();

        $request = $this->mapper->prepareRequest(
            $email,
            $prevUuid,
            $currUuid
        );

        $this->assertTrue($request[0]->valid());
        $this->assertEquals($email, $request[0]->getEmail());
        $this->assertEquals($currUuid, $request[0]->getUuid());

        $this->assertTrue($request[1]->valid());
        $this->assertEquals($email, $request[1]->getEmail());
        $this->assertEquals($prevUuid, $request[1]->getUuid());
    }
}