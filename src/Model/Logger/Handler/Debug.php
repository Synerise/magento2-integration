<?php

namespace Synerise\Integration\Model\Logger\Handler;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Debug extends Base
{
    const XML_PATH_DEBUG_LOGGER_ENABLED = 'synerise/debug/logger_enabled';

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param DriverInterface $filesystem
     * @param TimezoneInterface $timezone
     * @param ScopeConfigInterface $scopeConfig
     * @param string|null $filePath
     */
    public function __construct(
        DriverInterface $filesystem,
        TimezoneInterface $timezone,
        ScopeConfigInterface $scopeConfig,
        ?string $filePath = null
    ) {
        $this->timezone = $timezone;
        $this->scopeConfig = $scopeConfig;

        $fileName = '/var/log/synerise/debug-'.$this->timezone->date()->format('Y-m-d').'.log';

        parent::__construct($filesystem, $filePath, $fileName);
    }

    /**
     * Check that logging functionality is enabled
     *
     * @return bool
     */
    private function isLoggingEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::XML_PATH_DEBUG_LOGGER_ENABLED);
    }

    /**
     * @inheritdoc
     */
    public function isHandling(array $record): bool
    {
        return parent::isHandling($record) && $this->isLoggingEnabled();
    }
}