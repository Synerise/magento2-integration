<?php

namespace Synerise\Integration\SyneriseApi;

use GuzzleHttp\HandlerStack;

class Config
{
    CONST AUTHORIZATION_TYPE_BASIC = 'Basic';

    CONST AUTHORIZATION_TYPE_BEARER = 'Bearer';

    /**
     * @var string
     */
    private $scopeKey;

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

    public function __construct(
        string        $apiHost,
        string        $userAgent,
        ?float        $timeout = null,
        ?string       $scopeKey = null,
        ?string       $authorizationType = null,
        ?string       $authorizationToken = null,
        ?HandlerStack $handlerStack = null,
        ?bool         $keepAlive = false
    ) {
        $this->scopeKey = $scopeKey;
        $this->apiHost = $apiHost;
        $this->userAgent = $userAgent;
        $this->timeout = $timeout;
        $this->authorizationType = $authorizationType;
        $this->authorizationToken = $authorizationToken;
        $this->handlerStack = $handlerStack;
        $this->keepAlive = $keepAlive;
    }

    public function getScopeKey(): string
    {
        return $this->scopeKey;
    }

    public function getApiHost(): string
    {
        return $this->apiHost;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function getTimeout(): ?float
    {
        return $this->timeout;
    }

    public function getAuthorizationType(): ?string
    {
        return $this->authorizationType;
    }

    public function getAuthorizationToken(): ?string
    {
        return $this->authorizationToken;
    }

    public function getHandlerStack(): ?HandlerStack
    {
        return $this->handlerStack;
    }

    public function isKeepAliveEnabled(): ?bool
    {
        return $this->keepAlive;
    }
}