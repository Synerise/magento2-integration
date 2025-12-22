<?php
namespace Synerise\Integration\SyneriseApi\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Config\ReaderInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filter\TranslitUrl;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\Integration\Api\WorkspaceRepository;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Model\Workspace;
use Synerise\Integration\Model\WorkspaceInterface;
use Synerise\Sdk\Model\AuthenticationMethod;
use Synerise\Sdk\Model\AuthenticationMethodInterface;

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
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var WorkspaceRepository
     */
    private $workspaceRepository;

    /**
     * @param Logger $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param TranslitUrl $translitUrl
     * @param StoreManagerInterface $storeManager
     * @param SerializerInterface $serializer
     * @param WorkspaceRepository $workspaceRepository
     */
    public function __construct(
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        TranslitUrl $translitUrl,
        StoreManagerInterface $storeManager,
        SerializerInterface $serializer,
        WorkspaceRepository $workspaceRepository
    ) {
        $this->translitUrl = $translitUrl;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
        $this->workspaceRepository = $workspaceRepository;
    }

    /**
     * Read configuration
     *
     * @param mixed $scope
     * @return array
     */
    public function read($scope = null): array
    {
        $workspace = $this->getWorkspaceByStoreId($scope);

        return [
            'apiHost' => $workspace ? $workspace->getApiHost() : null,
            'apiKey' => $workspace ? $workspace->getApiKey() : null,
            'guid' => $workspace ? $workspace->getGuid() : null,
            'authenticationMethod' => $workspace ? ($workspace->isBasicAuthEnabled() ?
                AuthenticationMethodInterface::BASIC_VALUE : AuthenticationMethodInterface::BEARER_VALUE) : null,
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

    protected function getWorkspaceByStoreId(int $storeId): ?WorkspaceInterface
    {
        $websiteId = $this->getWebsiteIdByStoreId($storeId);
        $mapping = $this->getWorkspacesMapping();
            foreach ($mapping as $id => $workspaceId) {
            if ($websiteId == $id) {
                return $this->workspaceRepository->getById($websiteId);
            }
        }

        return null;
    }

    protected function getWebsiteIdByStoreId(int $storeId): int
    {
        return $this->storeManager
            ->getStore($storeId)
            ->getWebsiteId();
    }

    protected function getWorkspacesMapping(): array
    {
        $mapping = $this->scopeConfig->getValue(
            Workspace::XML_PATH_WORKSPACE_MAP
        );

        return $mapping ? $this->serializer->unserialize($mapping) : [];
    }
}
