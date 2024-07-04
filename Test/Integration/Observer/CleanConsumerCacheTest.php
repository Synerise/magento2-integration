<?php

namespace Synerise\Integration\Test\Integration\Observer;

use Magento\Framework\Event\Config;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Synerise\Integration\Observer\CleanConsumerCache;

class CleanConsumerCacheTest extends \PHPUnit\Framework\TestCase
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
        $observers = $this->eventConfig->getObservers('admin_system_config_changed_section_synerise_data');

        $this->assertArrayHasKey('synerise_changed_section_synerise_data', $observers);
        $expectedClass = CleanConsumerCache::class;
        $this->assertSame($expectedClass, $observers['synerise_changed_section_synerise_data']['instance']);

        $observers = $this->eventConfig->getObservers('admin_system_config_changed_section_synerise_event_tacking');

        $this->assertArrayHasKey('synerise_changed_section_synerise_data', $observers);
        $expectedClass = CleanConsumerCache::class;
        $this->assertSame($expectedClass, $observers['synerise_changed_section_synerise_data']['instance']);

        $observers = $this->eventConfig->getObservers('admin_system_config_changed_section_synerise_workspace');

        $this->assertArrayHasKey('synerise_clean_consumer_cache', $observers);
        $expectedClass = CleanConsumerCache::class;
        $this->assertSame($expectedClass, $observers['synerise_clean_consumer_cache']['instance']);
    }
}
