<?php
namespace Synerise\Integration\Model;

class ApiConfig
{
    /**
     * @var string|null
     */
    private $host;

    /**
     * @var float
     */
    private $timeout;

    /**
     * @var string|null
     */
    private $token;

    /**
     * @var bool
     */
    private $isLoggerEnabled;

    public function __construct(
        string $host,
        float $timeout,
        ?string $token = null,
        bool $isLoggerEnabled = false
    ) {
        $this->host = $host;
        $this->timeout = $timeout;
        $this->token = $token;
        $this->isLoggerEnabled = $isLoggerEnabled;
    }

    /**
     * @return string|null
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * @return float
     */
    public function getTimeout(): float
    {
        return $this->timeout;
    }

    /**
     * @return string|null
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * @return bool
     */
    public function isLoggerEnabled(): bool
    {
        return $this->isLoggerEnabled;
    }
}
