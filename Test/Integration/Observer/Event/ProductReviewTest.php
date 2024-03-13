<?php

namespace Synerise\Integration\Test\Integration\Observer\Event;

use Magento\Framework\Event\Config;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Synerise\Integration\Observer\Event\ProductReview;

class ProductReviewTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /**
     * @var Config
     */
    private $eventConfig;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->eventConfig = $this->objectManager->create(Config::class);
    }

    public function testObserverRegistration()
    {
        $observers = $this->eventConfig->getObservers('review_save_after');

        $this->assertArrayHasKey('synerise_review_save_after', $observers);
        $expectedClass = ProductReview::class;
        $this->assertSame($expectedClass, $observers['synerise_review_save_after']['instance']);

        $observers = $this->eventConfig->getObservers('controller_action_postdispatch_review_product_post');

        $this->assertArrayHasKey('synerise_controller_action_postdispatch_review_product_post', $observers);
        $expectedClass = ProductReview::class;
        $this->assertSame($expectedClass, $observers['synerise_controller_action_postdispatch_review_product_post']['instance']);
    }
}