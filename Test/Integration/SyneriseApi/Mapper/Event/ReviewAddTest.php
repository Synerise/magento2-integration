<?php

namespace Synerise\Integration\Test\Integration\SyneriseApi\Mapper\Event;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Review\Model\ResourceModel\Review\Collection;
use Magento\TestFramework\Helper\Bootstrap;
use Ramsey\Uuid\Uuid;
use Synerise\ApiClient\Model\Client;
use Synerise\ApiClient\Model\CustomeventRequest;
use Synerise\Integration\Observer\Event\ProductReview;
use Synerise\Integration\SyneriseApi\Mapper\Event\ReviewAdd;

class ReviewAddTest extends \PHPUnit\Framework\TestCase
{
    const FIXTURE_CUSTOMER_ID = 1;

    const LABEL = 'Customer reviewed product';

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ReviewAdd
     */
    private $mapper;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var Collection
     */
    private $reviewCollection;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->customerRepository = $this->objectManager->create(CustomerRepositoryInterface::class);
        $this->reviewCollection = $this->objectManager->create(Collection::class);

        $this->mapper = $this->objectManager->create(ReviewAdd::class);
    }

    /**
     * @magentoDataFixture Magento/Review/_files/customer_review_with_rating.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testPrepareRequest(): void
    {
        $review = $this->reviewCollection->getFirstItem();
        $uuid = (string) Uuid::Uuid4();

        $request = $this->mapper->prepareRequest(
            ProductReview::EVENT,
            $review,
            1,
            $uuid
        );

        $this->assertTrue($request->valid());
        $this->assertInstanceOf(CustomeventRequest::class, $request);

        $this->assertEquals(self::LABEL, $request->getLabel());

        $client = $request->getClient();

        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals($uuid, $client->getUuid());
        $this->assertNull($client->getEmail());
        $this->assertNull($client->getId());
        $this->assertNull($client->getCustomId());
    }
}