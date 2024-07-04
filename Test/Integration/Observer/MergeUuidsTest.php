<?php

namespace Synerise\Integration\Test\Integration\Observer;

use Magento\Framework\Event\Config;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Synerise\Integration\Observer\MergeUuids;

class MergeUuidsTest extends \PHPUnit\Framework\TestCase
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
        $observers = $this->eventConfig->getObservers('synerise_merge_uuids');

        $this->assertArrayHasKey('synerise_merge_uuids', $observers);
        $expectedClass = MergeUuids::class;
        $this->assertSame($expectedClass, $observers['synerise_merge_uuids']['instance']);
    }
}
