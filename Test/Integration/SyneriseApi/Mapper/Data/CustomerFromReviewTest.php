<?php

namespace Synerise\Integration\Test\Integration\SyneriseApi\Mapper\Data;

use Magento\Framework\ObjectManagerInterface;
use Magento\Review\Model\ResourceModel\Review;
use Magento\Review\Model\ResourceModel\Review\Collection;
use Magento\TestFramework\Helper\Bootstrap;
use Ramsey\Uuid\Uuid;
use Synerise\Integration\SyneriseApi\Mapper\Data\CustomerFromReview;

class CustomerFromReviewTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var CustomerFromReview
     */
    private $mapper;

    /**
     * @var Review
     */
    protected $reviewResource;

    /**
     * @var Collection
     */
    protected $reviewCollection;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->mapper = $this->objectManager->create(CustomerFromReview::class);
        $this->reviewCollection = $this->objectManager->create(Collection::class);
        $this->reviewResource =  $this->objectManager->create(Review::class);

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

        $request = $this->mapper->prepareRequest($review, $uuid);

        $this->assertTrue($request->valid());

        $this->assertEquals($uuid, $request->getUuid());
        $this->assertNull($request->getCustomId());
        $this->assertEquals($review->getNickname(), $request->getDisplayName());
    }
}