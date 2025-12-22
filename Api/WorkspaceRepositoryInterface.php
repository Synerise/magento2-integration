<?php

namespace Synerise\Integration\Api;

use Synerise\Integration\Model\WorkspaceInterface;

interface WorkspaceRepositoryInterface
{
    public function getById(int $id): WorkspaceInterface;

    public function save(WorkspaceInterface $workspace): WorkspaceInterface;

    public function delete(WorkspaceInterface $workspace): bool;
}