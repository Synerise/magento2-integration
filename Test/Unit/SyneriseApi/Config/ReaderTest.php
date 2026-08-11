<?php

namespace Synerise\Integration\Test\Unit\SyneriseApi\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filter\TranslitUrl;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Synerise\Integration\Api\WorkspaceRepositoryInterface;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Model\Workspace;
use Synerise\Integration\Model\WorkspaceInterface;
use Synerise\Integration\SyneriseApi\Config\Reader;

class ReaderTest extends TestCase
{
    private const STORE_ID = 1;
    private const WEBSITE_ID = 1;

    /**
     * @var WorkspaceRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $workspaceRepository;

    private function createReader(string $workspaceMapJson): Reader
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')
            ->willReturnCallback(
                fn (string $path) => $path === Workspace::XML_PATH_WORKSPACE_MAP ? $workspaceMapJson : null
            );
        $scopeConfig->method('isSetFlag')->willReturn(false);

        $store = $this->createMock(Store::class);
        $store->method('getWebsiteId')->willReturn(self::WEBSITE_ID);
        $store->method('getBaseUrl')->willReturn('https://example.com/');

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->with(self::STORE_ID)->willReturn($store);

        $translitUrl = $this->createMock(TranslitUrl::class);
        $translitUrl->method('filter')->willReturnArgument(0);

        $this->workspaceRepository = $this->createMock(WorkspaceRepositoryInterface::class);

        return new Reader(
            $this->createMock(Logger::class),
            $scopeConfig,
            $translitUrl,
            $storeManager,
            new Json(),
            $this->workspaceRepository
        );
    }

    public function testReadReturnsNullConfigWhenWebsiteMappingIsEmpty(): void
    {
        $reader = $this->createReader('{"' . self::WEBSITE_ID . '":""}');

        $this->workspaceRepository->expects($this->never())->method('getById');

        $config = $reader->read(self::STORE_ID);

        $this->assertNull($config['apiHost']);
        $this->assertNull($config['apiKey']);
        $this->assertNull($config['guid']);
        $this->assertNull($config['authenticationMethod']);
    }

    public function testReadReturnsNullConfigWhenWebsiteIsNotInMapping(): void
    {
        $reader = $this->createReader('{"99":"5"}');

        $this->workspaceRepository->expects($this->never())->method('getById');

        $config = $reader->read(self::STORE_ID);

        $this->assertNull($config['apiHost']);
        $this->assertNull($config['apiKey']);
    }

    public function testReadReturnsNullConfigWhenMappingIsNotConfigured(): void
    {
        $reader = $this->createReader('');

        $this->workspaceRepository->expects($this->never())->method('getById');

        $config = $reader->read(self::STORE_ID);

        $this->assertNull($config['apiHost']);
        $this->assertNull($config['apiKey']);
    }

    public function testReadReturnsNullConfigWhenMappedWorkspaceDoesNotExist(): void
    {
        $reader = $this->createReader('{"' . self::WEBSITE_ID . '":"5"}');

        $this->workspaceRepository->method('getById')
            ->willThrowException(new NoSuchEntityException(__('Workspace with ID %1 does not exist', 5)));

        $config = $reader->read(self::STORE_ID);

        $this->assertNull($config['apiHost']);
        $this->assertNull($config['apiKey']);
        $this->assertNull($config['guid']);
        $this->assertNull($config['authenticationMethod']);
    }

    public function testReadLoadsWorkspaceWhenMappingHoldsStringWorkspaceId(): void
    {
        $reader = $this->createReader('{"' . self::WEBSITE_ID . '":"5"}');

        $workspace = $this->createMock(WorkspaceInterface::class);
        $workspace->method('getApiHost')->willReturn('https://api.synerise.com');
        $workspace->method('getApiKey')->willReturn('api-key');

        $this->workspaceRepository->expects($this->once())
            ->method('getById')
            ->with(5)
            ->willReturn($workspace);

        $config = $reader->read(self::STORE_ID);

        $this->assertSame('https://api.synerise.com', $config['apiHost']);
        $this->assertSame('api-key', $config['apiKey']);
    }
}
