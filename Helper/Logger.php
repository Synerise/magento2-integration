<?php

namespace Synerise\Integration\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Model\Config\Source\Debug\Exclude;

class Logger implements LoggerInterface
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
        $exclusions = explode(',', (string) $this->scopeConfig->getValue(
            Exclude::XML_PATH_DEBUG_LOGGER_EXCLUDE,
        ));

        return in_array($exception, $exclusions);
    }

    /**
     * @inheritdoc
     */
    public function emergency($message, array $context = []): void
    {
        $this->getLogger()->emergency($message, $context);
    }

    /**
     * @inheritdoc
     */
    public function alert($message, array $context = []): void
    {
        $this->getLogger()->alert($message, $context);
    }

    /**
     * @inheritdoc
     */
    public function critical($message, array $context = []): void
    {
        $this->getLogger()->critical($message, $context);
    }

    /**
     * @inheritdoc
     */
    public function error($message, array $context = []): void
    {
        $this->getLogger()->error($message, $context);
    }

    /**
     * @inheritdoc
     */
    public function warning($message, array $context = []): void
    {
        $this->getLogger()->warning($message, $context);
    }

    /**
     * @inheritdoc
     */
    public function notice($message, array $context = []): void
    {
        $this->getLogger()->notice($message, $context);
    }

    /**
     * @inheritdoc
     */
    public function info($message, array $context = []): void
    {
        $this->getLogger()->info($message, $context);
    }

    /**
     * @inheritdoc
     */
    public function debug($message, array $context = []): void
    {
        $this->getLogger()->debug($message, $context);
    }

    /**
     * @inheritdoc
     */
    public function log($level, $message, array $context = []): void
    {
        $this->getLogger()->log($level, $message, $context);
    }
}
