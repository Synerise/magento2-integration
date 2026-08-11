<?php

namespace Synerise\Integration\Test\Unit\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;
use Synerise\Integration\Model\ResourceModel\Workspace as WorkspaceResource;
use Synerise\Integration\Model\Workspace;
use Synerise\Integration\Model\WorkspaceFactory;
use Synerise\Integration\Model\WorkspaceRepository;

class WorkspaceRepositoryTest extends TestCase
{
    /**
     * @var WorkspaceResource|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resource;

    private function createRepository(Workspace $workspace): WorkspaceRepository
    {
        $this->resource = $this->createMock(WorkspaceResource::class);

        $factory = $this->createMock(WorkspaceFactory::class);
        $factory->method('create')->willReturn($workspace);

        return new WorkspaceRepository($this->resource, $factory);
    }

    public function testGetByIdReturnsLoadedWorkspace(): void
    {
        $workspace = $this->createMock(Workspace::class);
        $workspace->method('getId')->willReturn(5);

        $repository = $this->createRepository($workspace);
        $this->resource->expects($this->once())->method('load')->with($workspace, 5);

        $this->assertSame($workspace, $repository->getById(5));
    }

    public function testGetByIdThrowsWhenWorkspaceDoesNotExist(): void
    {
        $workspace = $this->createMock(Workspace::class);
        $workspace->method('getId')->willReturn(null);

        $repository = $this->createRepository($workspace);

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('Workspace with ID 5 does not exist');

        $repository->getById(5);
    }

    public function testSaveDelegatesToResourceAndReturnsWorkspace(): void
    {
        $workspace = $this->createMock(Workspace::class);

        $repository = $this->createRepository($workspace);
        $this->resource->expects($this->once())->method('save')->with($workspace);

        $this->assertSame($workspace, $repository->save($workspace));
    }

    public function testDeleteDelegatesToResource(): void
    {
        $workspace = $this->createMock(Workspace::class);

        $repository = $this->createRepository($workspace);
        $this->resource->expects($this->once())->method('delete')->with($workspace);

        $this->assertTrue($repository->delete($workspace));
    }
}
