<?php

namespace Synerise\Integration\Test\Integration\Observer;


use Magento\TestFramework\Helper\Bootstrap;
use Synerise\Integration\Observer\NewsletterSubscriberDeleteAfter;

class NewsletterSubscriberDeleteAfterObserver extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\Event\Config $frameworkEvent
     */
    private $eventConfig;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->eventConfig = $this->objectManager->create(\Magento\Framework\Event\Config::class);
    }

    public function testObserverRegistration()
    {
        $observers = $this->eventConfig->getObservers('newsletter_subscriber_delete_after');

        $this->assertArrayHasKey('synerise_newsletter_subscriber_delete_after', $observers);
        $expectedClass = NewsletterSubscriberDeleteAfter::class;
        $this->assertSame($expectedClass, $observers['synerise_newsletter_subscriber_delete_after']['instance']);
    }
}