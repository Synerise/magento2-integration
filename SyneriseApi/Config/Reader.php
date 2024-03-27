<?php
namespace Synerise\Integration\SyneriseApi\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Config\ReaderInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filter\TranslitUrl;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\Integration\Helper\Logger;

class Reader implements ReaderInterface
{
    public const XML_PATH_API_LOGGER_ENABLED = 'synerise/api/logger_enabled';

    public const XML_PATH_API_KEEP_ALIVE_ENABLED = 'synerise/api/keep_alive_enabled';

    public const XML_PATH_API_SCHEDULED_REQUEST_TIMEOUT = 'synerise/api/scheduled_request_timeout';

    public const XML_PATH_API_LIVE_REQUEST_TIMEOUT = 'synerise/api/live_request_timeout';

    /**
     * @var TranslitUrl
     */
    private $translitUrl;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param Logger $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param TranslitUrl $translitUrl
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        TranslitUrl $translitUrl,
        StoreManagerInterface $storeManager
    ) {
        $this->translitUrl = $translitUrl;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Read configuration
     *
     * @param mixed $scope
     * @return array
     */
    public function read($scope = null): array
    {
        return [
            'userAgent' => $this->getUserAgent($scope),
            'isLoggerEnabled' => $this->isLoggerEnabled($scope),
            'isKeepAliveEnabled' => $this->isKeepAliveEnabled($scope),
            'liveRequestTimeout' => $this->getLiveRequestTimeout($scope),
            'scheduledRequestTimeout' => $this->getScheduledRequestTimeout($scope)
        ];
    }

    /**
     * Get user agent
     *
     * @param int $scopeId
     * @return string
     */
    public function getUserAgent(int $scopeId): string
    {
        $userAgent = 'magento2';

        try {
            $baseUrl = $this->storeManager->getStore($scopeId)->getBaseUrl();
            $domain = preg_replace('/^(http(s)?:\/\/)?((www.)?)/', '', $baseUrl);
            $userAgent .= '-' . $this->translitUrl->filter($domain);
        } catch (NoSuchEntityException $e) {
            $this->logger->debug('Store not found');
        }

        return $userAgent;
    }

    /**
     * Check if logger is enabled
     *
     * @param int $scopeId
     * @return bool
     */
    public function isLoggerEnabled(int $scopeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_API_LOGGER_ENABLED,
            $scopeId ? ScopeInterface::SCOPE_STORE : ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $scopeId
        );
    }

    /**
     * Check if keep-alive is enabled
     *
     * @param int $scopeId
     * @return bool
     */
    public function isKeepAliveEnabled(int $scopeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_API_KEEP_ALIVE_ENABLED,
            $scopeId ? ScopeInterface::SCOPE_STORE : ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $scopeId
        );
    }

    /**
     * Get live request timeout
     *
     * @param int $scopeId
     * @return string
     */
    public function getLiveRequestTimeout(int $scopeId): string
    {
        return (float) $this->scopeConfig->getValue(
            self::XML_PATH_API_LIVE_REQUEST_TIMEOUT,
            $scopeId ? ScopeInterface::SCOPE_STORE : ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $scopeId
        );
    }

    /**
     * Get scheduled request timeout
     *
     * @param int $scopeId
     * @return string
     */
    public function getScheduledRequestTimeout(int $scopeId): string
    {
        return (float) $this->scopeConfig->getValue(
            self::XML_PATH_API_SCHEDULED_REQUEST_TIMEOUT,
            $scopeId ? ScopeInterface::SCOPE_STORE : ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $scopeId
        );
    }
}
