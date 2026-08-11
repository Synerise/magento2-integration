<?php

namespace Synerise\Integration\Api;

use Magento\Framework\Exception\NoSuchEntityException;
use Synerise\Integration\Model\WorkspaceInterface;

interface WorkspaceRepositoryInterface
{
    /**
     * Get workspace by ID
     *
     * @param int $id
     * @return WorkspaceInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $id): WorkspaceInterface;

    /**
     * Save workspace
     *
     * @param WorkspaceInterface $workspace
     * @return WorkspaceInterface
     */
    public function save(WorkspaceInterface $workspace): WorkspaceInterface;

    /**
     * Delete workspace
     *
     * @param WorkspaceInterface $workspace
     * @return bool
     */
    public function delete(WorkspaceInterface $workspace): bool;
}