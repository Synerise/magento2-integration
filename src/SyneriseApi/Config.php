<?php

namespace Synerise\Integration\SyneriseApi;

use GuzzleHttp\HandlerStack;

class Config
{
    public const AUTHORIZATION_TYPE_BASIC = 'Basic';

    public const AUTHORIZATION_TYPE_BEARER = 'Bearer';

    public const MODE_LIVE = 'live';

    public const MODE_SCHEDULE = 'schedule';

    /**
     * @var string
     */
    private $apiHost;

    /**
     * @var string
     */
    private $userAgent;

    /**
     * @var float|null
     */
    private $timeout;

    /**
     * @var string|null
     */
    private $authorizationType;

    /**
     * @var string|null
     */
    private $authorizationToken;

    /**
     * @var HandlerStack|null
     */
    private $handlerStack;

    /**
     * @var bool|null
     */
    private $keepAlive;

    /**
     * @param string $apiHost
     * @param string $userAgent
     * @param float|null $timeout
     * @param string|null $authorizationType
     * @param string|null $authorizationToken
     * @param HandlerStack|null $handlerStack
     * @param bool|null $keepAlive
     */
    public function __construct(
        string $apiHost,
        string $userAgent,
        ?float $timeout = null,
        ?string $authorizationType = null,
        ?string $authorizationToken = null,
        ?HandlerStack $handlerStack = null,
        ?bool $keepAlive = false
    ) {
        $this->apiHost = $apiHost;
        $this->userAgent = $userAgent;
        $this->timeout = $timeout;
        $this->authorizationType = $authorizationType;
        $this->authorizationToken = $authorizationToken;
        $this->handlerStack = $handlerStack;
        $this->keepAlive = $keepAlive;
    }

    /**
     * Get API hosts
     *
     * @return string
     */
    public function getApiHost(): string
    {
        return $this->apiHost;
    }

    /**
     * Get User Agent
     *
     * @return string
     */
    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * Get timeout
     *
     * @return float|null
     */
    public function getTimeout(): ?float
    {
        return $this->timeout;
    }

    /**
     * Get authorization type
     *
     * @return string|null
     */
    public function getAuthorizationType(): ?string
    {
        return $this->authorizationType;
    }

    /**
     * Get authorization token
     *
     * @return string|null
     */
    public function getAuthorizationToken(): ?string
    {
        return $this->authorizationToken;
    }

    /**
     * Get handler stack
     *
     * @return HandlerStack|null
     */
    public function getHandlerStack(): ?HandlerStack
    {
        return $this->handlerStack;
    }

    /**
     * Check if keep alive is enabled
     *
     * @return bool|null
     */
    public function isKeepAliveEnabled(): ?bool
    {
        return $this->keepAlive;
    }
}
