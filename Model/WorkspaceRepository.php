<?php

namespace Synerise\Integration\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Synerise\Integration\Api\WorkspaceRepositoryInterface;
use Synerise\Integration\Model\ResourceModel\Workspace as WorkspaceResource;
use Synerise\Integration\Model\WorkspaceFactory;
use Synerise\Integration\Model\WorkspaceInterface;

class WorkspaceRepository implements WorkspaceRepositoryInterface
{
    /**
     * @var WorkspaceResource
     */
    private $resource;

    /**
     * @var WorkspaceFactory
     */
    private $factory;

    public function __construct(
        WorkspaceResource $resource,
        WorkspaceFactory $factory
    ) {
        $this->resource = $resource;
        $this->factory = $factory;
    }


    public function getById(int $id): WorkspaceInterface
    {
        $workspace = $this->factory->create();
        $this->resource->load($workspace, $id);

        if (!$workspace->getId()) {
            throw new NoSuchEntityException(__('Workspace with ID %1 does not exist', $id));
        }

        return $workspace;
    }

    public function save(WorkspaceInterface $workspace): WorkspaceInterface
    {
        $this->resource->save($workspace);
        return $workspace;
    }

    public function delete(WorkspaceInterface $workspace): bool
    {
        $this->resource->delete($workspace);
        return true;
    }
}