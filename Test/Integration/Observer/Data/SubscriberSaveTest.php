<?php

namespace Synerise\Integration\Test\Integration\Observer\Data;

use Magento\Framework\Event\Config;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Synerise\Integration\Observer\Data\SubscriberSave;

class SubscriberSaveTest extends \PHPUnit\Framework\TestCase
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
        $observers = $this->eventConfig->getObservers('newsletter_subscriber_save_after');

        $this->assertArrayHasKey('synerise_newsletter_subscriber_save_after', $observers);
        $expectedClass = SubscriberSave::class;
        $this->assertSame($expectedClass, $observers['synerise_newsletter_subscriber_save_after']['instance']);
    }
}
