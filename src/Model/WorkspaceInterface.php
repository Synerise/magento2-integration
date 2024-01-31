<?php
namespace Synerise\Integration\Model;

interface WorkspaceInterface
{
    /**
     * Check if API key is set
     *
     * @return bool
     */
    public function isApiKeySet();

    /**
     * Get API key
     *
     * @return string|null
     */
    public function getApiKey();

    /**
     * Get GUID
     *
     * @return string|null
     */
    public function getGuid(): ?string;
}
