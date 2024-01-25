<?php

namespace Synerise\Integration\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Model\Config\Source\Debug\Exclude;

class Logger
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get PSR logger
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Check if exception should be excluded from logging
     *
     * @param string $exception
     * @return bool
     */
    public function isExcludedFromLogging(string $exception): bool
    {
        $exclusions = explode(',', $this->scopeConfig->getValue(
            Exclude::XML_PATH_DEBUG_LOGGER_EXCLUDE,
        ));

        return in_array($exception, $exclusions);
    }
}
