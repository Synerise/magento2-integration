<?php

namespace Synerise\Integration\Test\Unit\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\TestCase;
use Synerise\ApiClient\Api\TrackerControllerApi;
use Synerise\ApiClient\Model\TrackingCodeCreationByDomainRequest;
use Synerise\ApiClient\Model\TrackingCodeResponse;
use Synerise\Integration\Block\Tracking\Code;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\Model\Workspace;
use Synerise\Integration\Observer\UpdateTrackerKey;
use Synerise\Integration\SyneriseApi\Config;
use Synerise\Integration\SyneriseApi\ConfigFactory as ApiConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory as ApiInstanceFactory;

class UpdateTrackerKeyTest extends TestCase
{
    private const WEBSITE_ID = 1;
    private const STORE_ID = 10;
    private const WORKSPACE_ID = 5;

    /**
     * @var WriterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configWriter;

    /**
     * @var array[] all configWriter->save() calls as argument lists
     */
    private $savedConfig = [];

    private function createObserverModel(?Workspace $loadedWorkspace): UpdateTrackerKey
    {
        $this->savedConfig = [];

        $objectManager = $this->createMock(ObjectManagerInterface::class);
        if ($loadedWorkspace) {
            $objectManager->method('create')->with(Workspace::class)->willReturn($loadedWorkspace);
        }

        $config = $this->createMock(ScopeConfigInterface::class);
        $config->method('getValue')->willReturnCallback(
            fn (string $path) => $path === Cookie::XML_PATH_PAGE_TRACKING_DOMAIN ? '.example.com.' : null
        );

        $this->configWriter = $this->createMock(WriterInterface::class);
        $this->configWriter->method('save')->willReturnCallback(
            function (...$args): void {
                $this->savedConfig[] = $args;
            }
        );

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $website = $this->createMock(Website::class);
        $website->method('getStoreIds')->willReturn([self::STORE_ID]);
        $storeManager->method('getWebsite')->with(self::WEBSITE_ID)->willReturn($website);

        $trackingCodeResponse = $this->createMock(TrackingCodeResponse::class);
        $trackingCodeResponse->method('getCode')->willReturn('tracker-key-123');

        $trackerApi = $this->createMock(TrackerControllerApi::class);
        $trackerApi->method('getOrCreateByDomain')
            ->with($this->callback(
                fn (TrackingCodeCreationByDomainRequest $request) => $request->getDomain() === 'example.com'
            ))
            ->willReturn($trackingCodeResponse);

        $apiConfigFactory = $this->createMock(ApiConfigFactory::class);
        $apiConfigFactory->method('create')->willReturn($this->createMock(Config::class));

        $apiInstanceFactory = $this->createMock(ApiInstanceFactory::class);
        $apiInstanceFactory->method('createApiInstance')->willReturn($trackerApi);

        return new UpdateTrackerKey(
            $objectManager,
            $config,
            $this->configWriter,
            new Json(),
            $storeManager,
            $apiConfigFactory,
            $apiInstanceFactory
        );
    }

    private function loadableWorkspace(int $id): Workspace
    {
        $workspace = $this->createMock(Workspace::class);
        $workspace->method('load')->with($id)->willReturnSelf();
        $workspace->method('getId')->willReturn($id);
        $workspace->method('getTrackerHost')->willReturn('web.snrbox.com');

        return $workspace;
    }

    public function testMappingChangeUpdatesTrackerKeyAndHostForWebsiteStores(): void
    {
        $model = $this->createObserverModel($this->loadableWorkspace(self::WORKSPACE_ID));

        $model->execute(new Observer([
            'website_id' => self::WEBSITE_ID,
            'workspace_id' => (string) self::WORKSPACE_ID,
        ]));

        $this->assertSame(
            [
                [Code::XML_PATH_PAGE_TRACKING_KEY, 'tracker-key-123', ScopeInterface::SCOPE_STORES, self::STORE_ID],
                [Code::XML_PATH_PAGE_TRACKING_HOST, 'web.snrbox.com', ScopeInterface::SCOPE_STORES, self::STORE_ID],
            ],
            $this->savedConfig
        );
    }

    public function testMappingRemovalDeletesTrackerKeyForWebsiteStores(): void
    {
        $model = $this->createObserverModel(null);

        $this->configWriter->expects($this->once())
            ->method('delete')
            ->with(Code::XML_PATH_PAGE_TRACKING_KEY, ScopeInterface::SCOPE_STORES, self::STORE_ID);

        $model->execute(new Observer([
            'website_id' => self::WEBSITE_ID,
            'workspace_id' => '',
        ]));

        $this->assertSame([], $this->savedConfig);
    }
}
