<?php

namespace Synerise\Integration\Test\Unit\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;
use Synerise\Integration\Model\Config\Backend\Workspaces;
use Synerise\Integration\Model\Workspace;

class WorkspacesTest extends TestCase
{
    private const MAPPING_CHANGED_EVENT = 'synerise_workspace_mapping_changed';

    /**
     * @var array[] all events dispatched by the model as [name, data] pairs
     */
    private $dispatchedEvents = [];

    private function createModel(?string $oldValueJson): Workspaces
    {
        $this->dispatchedEvents = [];

        $eventManager = $this->createMock(ManagerInterface::class);
        $eventManager->method('dispatch')->willReturnCallback(
            function (string $name, array $data = []): void {
                $this->dispatchedEvents[] = [$name, $data];
            }
        );

        $context = $this->createMock(Context::class);
        $context->method('getEventDispatcher')->willReturn($eventManager);

        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            fn (string $path) => $path === Workspace::XML_PATH_WORKSPACE_MAP ? $oldValueJson : null
        );

        $model = new Workspaces(
            $context,
            $this->createMock(Registry::class),
            $scopeConfig,
            $this->createMock(TypeListInterface::class),
            new Json()
        );
        $model->setPath(Workspace::XML_PATH_WORKSPACE_MAP);

        return $model;
    }

    private function mappingChangedEvents(): array
    {
        return array_values(array_filter(
            $this->dispatchedEvents,
            fn (array $event) => $event[0] === self::MAPPING_CHANGED_EVENT
        ));
    }

    public function testBeforeSaveSerializesMappingAndStripsEmptyRow(): void
    {
        $model = $this->createModel(null);
        $model->setValue(['1' => '5', '__empty' => '']);

        $model->beforeSave();

        $this->assertSame('{"1":"5"}', $model->getValue());
    }

    public function testBeforeSaveDispatchesEventForNewlyMappedWebsite(): void
    {
        $model = $this->createModel(null);
        $model->setValue(['1' => '5']);

        $model->beforeSave();

        $events = $this->mappingChangedEvents();
        $this->assertCount(1, $events);
        $this->assertSame(['workspace_id' => '5', 'website_id' => 1], $events[0][1]);
    }

    public function testBeforeSaveDispatchesOnlyForChangedMappings(): void
    {
        $model = $this->createModel('{"1":"5","2":"7"}');
        $model->setValue(['1' => '5', '2' => '9']);

        $model->beforeSave();

        $events = $this->mappingChangedEvents();
        $this->assertCount(1, $events);
        $this->assertSame(['workspace_id' => '9', 'website_id' => 2], $events[0][1]);
    }

    public function testBeforeSaveWithAllRowsRemovedDispatchesNoMappingEvent(): void
    {
        $model = $this->createModel('{"1":"5"}');
        $model->setValue(['__empty' => '']);

        $model->beforeSave();

        $this->assertSame([], $this->mappingChangedEvents());
        $this->assertSame('[]', $model->getValue());
    }

    public function testAfterLoadDecodesStoredJson(): void
    {
        $model = $this->createModel(null);
        $model->setValue('{"1":"5"}');

        $model->afterLoad();

        $this->assertSame(['1' => '5'], $model->getValue());
    }

    public function testAfterLoadWithEmptyValueSetsNull(): void
    {
        $model = $this->createModel(null);
        $model->setValue('');

        $model->afterLoad();

        $this->assertNull($model->getValue());
    }
}
