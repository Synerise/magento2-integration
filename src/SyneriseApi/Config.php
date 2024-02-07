<?php

namespace Synerise\Integration\SyneriseApi;

use Synerise\Integration\SyneriseApi\Config\Data;
use Synerise\Integration\SyneriseApi\Config\DataFactory;

class Config implements ConfigInterface
{
    /**
     * @var Data
     */
    protected $dataStorage;

    /**
     * @var int
     */
    protected $storeId;

    /**
     * @var string
     */
    private $mode;

    /**
     * @var float
     */
    private $timeout = 2.5;

    /**
     * @param DataFactory $dataFactory
     * @param int $storeId
     */
    public function __construct(DataFactory $dataFactory, int $storeId)
    {
        $this->dataStorage = $dataFactory->create($storeId);
        $this->storeId = $storeId;

        // phpcs:ignore
        $this->mode = isset($_SERVER['REQUEST_METHOD']) ? ConfigInterface::MODE_LIVE : ConfigInterface::MODE_SCHEDULE;
    }

    /**
     * Get User Agent
     *
     * @return string
     */
    public function getUserAgent(): string
    {
        return $this->dataStorage->get('userAgent');
    }

    /**
     * Get timeout
     *
     * @return float
     */
    public function getTimeout(): float
    {
        if ($this->mode == ConfigInterface::MODE_LIVE) {
            return $this->dataStorage->get('liveRequestTimeout', $this->timeout);
        } elseif ($this->mode == ConfigInterface::MODE_SCHEDULE) {
            return $this->dataStorage->get('scheduledRequestTimeout', $this->timeout);
        }
        return $this->timeout;
    }

    /**
     * Check if keep alive is enabled
     *
     * @return bool
     */
    public function isKeepAliveEnabled(): bool
    {
        return $this->dataStorage->get('isKeepAliveEnabled', false);
    }

    /**
     * Check if request logging is enabled
     *
     * @return bool
     */
    public function isLoggerEnabled(): bool
    {
        return $this->dataStorage->get('isLoggerEnabled', false);
    }
}
