<?php

namespace Synerise\Integration\Test\Integration\Synchronization;

use Magento\TestFramework\Helper\Bootstrap;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Api\Bags;
use Synerise\Integration\Helper\Api\BagsFactory;

class BagsFactoryTest extends \PHPUnit\Framework\TestCase
{
    const STORE_ID = 1;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Api
     */
    private $apiHelper;

    /**
     * @var BagsFactory
     */
    private $bagsFactory;

    protected function setUp(): void
    {
        Bootstrap::getInstance()->reinitialize();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->apiHelper = $this->objectManager->create(Api::class);
        $this->bagsFactory = $this->objectManager->create(BagsFactory::class);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testCreate()
    {
        /** @var Bags $helper */
        $helper = $this->bagsFactory->create($this->apiHelper->getApiConfigByScope(self::STORE_ID));

        $this->assertInstanceOf(Bags::class, $helper);
    }

}