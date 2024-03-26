<?php

namespace Synerise\Integration\Test\Integration\Observer\Event;

use Magento\Framework\Event\Config;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Synerise\Integration\Observer\Event\CustomerLogout;

class CustomerLogoutTest extends \PHPUnit\Framework\TestCase
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
        $observers = $this->eventConfig->getObservers('customer_logout');

        $this->assertArrayHasKey('synerise_customer_logout_observer', $observers);
        $expectedClass = CustomerLogout::class;
        $this->assertSame($expectedClass, $observers['synerise_customer_logout_observer']['instance']);
    }
}