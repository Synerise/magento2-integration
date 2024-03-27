<?php

namespace Synerise\Integration\SyneriseApi;

interface ConfigInterface
{
    public const AUTHORIZATION_TYPE_BASIC = 'Basic';

    public const AUTHORIZATION_TYPE_BEARER = 'Bearer';

    public const MODE_LIVE = 'live';

    public const MODE_SCHEDULE = 'schedule';

    /**
     * Get User Agent
     *
     * @return string
     */
    public function getUserAgent(): string;

    /**
     * Get timeout
     *
     * @return float|null
     */
    public function getTimeout(): ?float;

    /**
     * Check if request logging is enabled
     *
     * @return bool
     */
    public function isLoggerEnabled(): bool;

    /**
     * Check if keep alive is enabled
     *
     * @return bool|null
     */
    public function isKeepAliveEnabled(): bool;
}
