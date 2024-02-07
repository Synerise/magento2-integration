<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\ApiClient\Api\TrackerControllerApi;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\TrackingCodeCreationByDomainRequest;
use Synerise\Integration\Block\Tracking\Code;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\Model\Workspace;
use Synerise\Integration\SyneriseApi\ConfigFactory as ApiConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory as ApiInstanceFactory;

class UpdateTrackerKey implements ObserverInterface
{
    public const ERROR_MSG_403 = 'Please make sure this api key has TRACKER_CREATE permission.';

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $config;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ApiConfigFactory
     */
    protected $apiConfigFactory;

    /**
     * @var ApiInstanceFactory
     */
    protected $apiInstanceFactory;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param ScopeConfigInterface $config
     * @param WriterInterface $configWriter
     * @param SerializerInterface $serializer
     * @param StoreManagerInterface $storeManager
     * @param ApiConfigFactory $apiConfigFactory
     * @param ApiInstanceFactory $apiInstanceFactory
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ScopeConfigInterface $config,
        WriterInterface $configWriter,
        SerializerInterface $serializer,
        StoreManagerInterface $storeManager,
        ApiConfigFactory $apiConfigFactory,
        ApiInstanceFactory $apiInstanceFactory
    ) {
        $this->objectManager = $objectManager;
        $this->config = $config;
        $this->configWriter = $configWriter;
        $this->serializer = $serializer;
        $this->storeManager = $storeManager;
        $this->apiConfigFactory = $apiConfigFactory;
        $this->apiInstanceFactory = $apiInstanceFactory;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return void
     * @throws LocalizedException|ApiException
     */
    public function execute(Observer $observer)
    {
        $workspace = $observer->getObject();
        if ($workspace) {
            foreach ($this->getWorkspaceMap() as $websiteId => $workspaceId) {
                if ($workspace->getId() == $workspaceId) {
                    $this->updateTrackerKeys(
                        $this->storeManager->getWebsite($websiteId)->getStoreIds(),
                        $workspace
                    );
                }
            }
        } else {
            $websiteId = $observer->getData('website_id');
            if ($websiteId) {
                $workspaceId = (int) $observer->getData('workspace_id');
                $this->updateTrackerKeys(
                    $this->storeManager->getWebsite($websiteId)->getStoreIds(),
                    $workspaceId ? $this->objectManager->create(Workspace::class)->load($workspaceId) : null
                );
            }
        }
    }

    /**
     * Get config domain
     *
     * @param int $storeId
     * @return string|null
     */
    protected function getConfigDomain(int $storeId): ?string
    {
        $domain = $this->config->getValue(
            Cookie::XML_PATH_PAGE_TRACKING_DOMAIN,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );

        return $domain ? trim($domain, '.') : null;
    }

    /**
     * Get Base URL domain
     *
     * @param int $storeId
     * @return string
     */
    protected function getBaseUrlDomain(int $storeId): string
    {
        $baseUrl = $this->config->getValue(
            Store::XML_PATH_UNSECURE_BASE_LINK_URL,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $parsedUrl = parse_url($baseUrl);
        return $parsedUrl['host'];
    }

    /**
     * Get workspace to website mapping
     *
     * @return array
     */
    protected function getWorkspaceMap(): array
    {
        return $this->serializer->unserialize($this->config->getValue(Workspace::XML_PATH_WORKSPACE_MAP));
    }

    /**
     * Update tracker key for associated stores
     *
     * @param array $storeIds
     * @param Workspace|null $workspace
     * @return void
     * @throws ApiException
     * @throws LocalizedException
     * @throws ValidatorException
     */
    protected function updateTrackerKeys(array $storeIds, ?Workspace $workspace = null)
    {
        if ($workspace && $workspace->getId()) {
            foreach ($storeIds as $storeId) {
                $this->configWriter->save(
                    Code::XML_PATH_PAGE_TRACKING_KEY,
                    $this->getTrackerKey($workspace, $storeId),
                    ScopeInterface::SCOPE_STORES,
                    $storeId
                );
                $this->configWriter->save(
                    Code::XML_PATH_PAGE_TRACKING_HOST,
                    $workspace->getTrackerHost(),
                    ScopeInterface::SCOPE_STORES,
                    $storeId
                );
            }
        } else {
            foreach ($storeIds as $storeId) {
                $this->configWriter->delete(
                    Code::XML_PATH_PAGE_TRACKING_KEY,
                    ScopeInterface::SCOPE_STORES,
                    $storeId
                );
            }
        }
    }

    /**
     * Get tracker key by domain
     *
     * @param Workspace $workspace
     * @param int $storeId
     * @return string|null
     * @throws ApiException
     * @throws LocalizedException
     * @throws ValidatorException
     */
    protected function getTrackerKey(Workspace $workspace, int $storeId): ?string
    {
        try {
            $response = $this->getTrackerApiInstance(
                $storeId,
                $workspace
            )->getOrCreateByDomain(new TrackingCodeCreationByDomainRequest([
                'domain' => $this->getConfigDomain($storeId) ?? $this->getBaseUrlDomain($storeId)
            ]));
            return $response->getCode();
        } catch (ApiException $e) {
            if ($e->getCode() == 403) {
                $message = self::ERROR_MSG_403;
                throw new LocalizedException(__($message));
            } else {
                throw $e;
            }
        }
    }

    /**
     * Get Tracker API instance
     *
     * @param int $storeId
     * @param Workspace $workspace
     * @return TrackerControllerApi
     * @throws ApiException
     * @throws ValidatorException
     */
    private function getTrackerApiInstance(int $storeId, Workspace $workspace): TrackerControllerApi
    {
        return $this->apiInstanceFactory->createApiInstance(
            'tracker',
            $this->apiConfigFactory->create($storeId),
            $workspace
        );
    }
}
