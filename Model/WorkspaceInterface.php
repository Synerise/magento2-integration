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
     * Get API host
     *
     * @return string
     */
    public function getApiHost(): string;

    /**
     * Get tracker host
     *
     * @return string
     */
    public function getTrackerHost(): string;

    /**
     * Get GUID
     *
     * @return string|null
     */
    public function getGuid(): ?string;

    /**
     * Check if basic auth is enabled
     *
     * @return bool
     */
    public function isBasicAuthEnabled(): bool;
}
