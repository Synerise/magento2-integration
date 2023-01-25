<?php
namespace Synerise\Integration\Model;


class ApiConfig
{
    public function __construct($host, $token = null, $isLoggerEnabled = false)
    {
        $this->host = $host;
        $this->token = $token;
        $this->isLoggerEnabled = $isLoggerEnabled;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function isLoggerEnabled()
    {
        return $this->isLoggerEnabled;
    }
}
