<?php

namespace Synerise\Integration\Test\Unit\Model\Workspace\Config;

use ArrayIterator;
use Magento\Config\Model\ResourceModel\Config\Data\Collection as ConfigCollection;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigCollectionFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ResourceModel\Website\Collection as WebsiteCollection;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;
use PHPUnit\Framework\TestCase;
use Synerise\Integration\Model\ResourceModel\Workspace\Collection as WorkspaceCollection;
use Synerise\Integration\Model\ResourceModel\Workspace\CollectionFactory as WorkspaceCollectionFactory;
use Synerise\Integration\Model\Workspace;
use Synerise\Integration\Model\Workspace\Config\Reader;

class ReaderTest extends TestCase
{
    private function createReader(string $mappingJson, array $websites, array $workspaces): Reader
    {
        $configItem = new DataObject(['value' => $mappingJson]);
        $configCollection = $this->createMock(ConfigCollection::class);
        $configCollection->method('addFieldToFilter')->willReturnSelf();
        $configCollection->method('getItems')->willReturn([$configItem]);
        $configCollectionFactory = $this->createMock(ConfigCollectionFactory::class);
        $configCollectionFactory->method('create')->willReturn($configCollection);

        $websiteCollection = $this->createMock(WebsiteCollection::class);
        $websiteCollection->method('addFieldToFilter')->willReturnSelf();
        $websiteCollection->method('getIterator')->willReturn(new ArrayIterator($websites));
        $websiteCollectionFactory = $this->createMock(WebsiteCollectionFactory::class);
        $websiteCollectionFactory->method('create')->willReturn($websiteCollection);

        $workspaceCollection = $this->createMock(WorkspaceCollection::class);
        $workspaceCollection->method('addFieldToFilter')->willReturnSelf();
        $workspaceCollection->method('getIterator')->willReturn(new ArrayIterator($workspaces));
        $workspaceCollectionFactory = $this->createMock(WorkspaceCollectionFactory::class);
        $workspaceCollectionFactory->method('create')->willReturn($workspaceCollection);

        return new Reader(
            $configCollectionFactory,
            $websiteCollectionFactory,
            new Json(),
            $workspaceCollectionFactory
        );
    }

    private function website(int $websiteId, array $storeIds): Website
    {
        $stores = [];
        foreach ($storeIds as $storeId) {
            $store = $this->createMock(Store::class);
            $store->method('getId')->willReturn($storeId);
            $stores[] = $store;
        }

        $website = $this->createMock(Website::class);
        $website->method('getId')->willReturn($websiteId);
        $website->method('getStores')->willReturn($stores);

        return $website;
    }

    private function workspace(int $workspaceId): Workspace
    {
        $workspace = $this->createMock(Workspace::class);
        $workspace->method('getId')->willReturn($workspaceId);
        $workspace->method('getApiHost')->willReturn('https://api.synerise.com');
        $workspace->method('getApiKey')->willReturn('api-key-' . $workspaceId);
        $workspace->method('isBasicAuthEnabled')->willReturn(false);
        $workspace->method('getGuid')->willReturn('guid-' . $workspaceId);
        $workspace->method('getTrackerHost')->willReturn('web.snrbox.com');

        return $workspace;
    }

    public function testReadReturnsTrackerConfigPerStoreOfMappedWebsite(): void
    {
        $reader = $this->createReader(
            '{"1":"5"}',
            [$this->website(1, [10, 11])],
            [$this->workspace(5)]
        );

        $output = $reader->read();

        $this->assertSame([10, 11], array_keys($output));
        $this->assertSame(
            [
                'apiHost' => 'https://api.synerise.com',
                'apiKey' => 'api-key-5',
                'isBasicAuthEnabled' => false,
                'guid' => 'guid-5',
                'trackerHost' => 'web.snrbox.com',
            ],
            $output[10]
        );
    }

    public function testReadReturnsEmptyConfigWhenAllWebsitesAreUnmapped(): void
    {
        $reader = $this->createReader('{"1":""}', [$this->website(1, [10])], []);

        $this->assertSame([], $reader->read());
    }

    public function testReadSkipsWebsiteWhoseWorkspaceWasDeleted(): void
    {
        $reader = $this->createReader('{"1":"5"}', [$this->website(1, [10])], []);

        $this->assertSame([], $reader->read());
    }
}
