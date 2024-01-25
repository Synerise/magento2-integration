<?php

namespace Synerise\Integration\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Store\Model\Store;
use Synerise\ApiClient\Api\TrackerControllerApi;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\TrackingCodeCreationByDomainRequest;
use Synerise\Integration\Block\Tracking\Code;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\Model\Workspace as WorkspaceModel;
use Synerise\Integration\SyneriseApi\ConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory;

class Workspace extends Value
{
    public const XML_PATH_API_KEY = 'synerise/api/key';
    public const XML_PATH_API_BASIC_TOKEN = 'synerise/api/basic_token';
    public const ERROR_MSG_403 = 'Please make sure this api key has all required roles.';

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var WorkspaceModel
     */
    protected $workspace;

    /**
     * @var ConfigFactory
     */
    private $configFactory;

    /**
     * @var InstanceFactory
     */
    private $apiInstanceFactory;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param WriterInterface $configWriter
     * @param EncryptorInterface $encryptor
     * @param WorkspaceModel $workspace
     * @param ConfigFactory $configFactory
     * @param InstanceFactory $apiInstanceFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        WriterInterface $configWriter,
        EncryptorInterface $encryptor,
        WorkspaceModel $workspace,
        ConfigFactory $configFactory,
        InstanceFactory $apiInstanceFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->configWriter = $configWriter;
        $this->encryptor = $encryptor;
        $this->workspace = $workspace;
        $this->configFactory = $configFactory;
        $this->apiInstanceFactory = $apiInstanceFactory;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Validate workspace
     *
     * @throws ApiException
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $workspaceId = (int) $this->getValue();
        if ($workspaceId) {
            $workspace = $this->workspace->load($workspaceId);

            try {
                $response = $this->getTrackerApiInstance(
                    $this->getScope(),
                    $this->getScopeId(),
                    $workspace->getApiKey()
                )->getOrCreateByDomain(new TrackingCodeCreationByDomainRequest([
                    'domain' => $this->getConfigDomain() ?? $this->getBaseUrlDomain()
                ]));
            } catch (ApiException $e) {
                if ($e->getCode() == 403) {
                    $message = self::ERROR_MSG_403;
                    throw new LocalizedException(__($message));
                } else {
                    throw $e;
                }
            }

            $this->configWriter->save(
                self::XML_PATH_API_KEY,
                $workspace->getData('api_key'),
                $this->getScope(),
                $this->getScopeId()
            );

            $guid = $workspace->getGuid();
            if ($guid) {
                $this->configWriter->save(
                    self::XML_PATH_API_BASIC_TOKEN,
                    $this->encryptor->encrypt(base64_encode("{$guid}:{$workspace->getApiKey()}")),
                    $this->getScope(),
                    $this->getScopeId()
                );
            } else {
                $this->configWriter->delete(
                    self::XML_PATH_API_BASIC_TOKEN,
                    $this->getScope(),
                    $this->getScopeId()
                );
            }

            $this->configWriter->save(
                Code::XML_PATH_PAGE_TRACKING_KEY,
                $response->getCode(),
                $this->getScope(),
                $this->getScopeId()
            );
        } else {
            $this->configWriter->delete(
                self::XML_PATH_API_KEY,
                $this->getScope(),
                $this->getScopeId()
            );

            $this->configWriter->delete(
                self::XML_PATH_API_BASIC_TOKEN,
                $this->getScope(),
                $this->getScopeId()
            );

            $this->configWriter->delete(
                Code::XML_PATH_PAGE_TRACKING_KEY,
                $this->getScope(),
                $this->getScopeId()
            );
        }

        parent::beforeSave();
    }

    /**
     * Get config domain
     *
     * @return string|null
     */
    private function getConfigDomain(): ?string
    {
        $domain = $this->_config->getValue(
            Cookie::XML_PATH_PAGE_TRACKING_DOMAIN,
            $this->getScope(),
            $this->getScopeId()
        );

        return $domain ? trim($domain, '.') : null;
    }

    /**
     * Get Base URL domain
     *
     * @return string
     */
    private function getBaseUrlDomain(): string
    {
        $baseUrl = $this->_config->getValue(
            Store::XML_PATH_UNSECURE_BASE_LINK_URL,
            $this->getScope(),
            $this->getScopeId()
        );

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $parsedUrl = parse_url($baseUrl);
        return $parsedUrl['host'];
    }

    /**
     * Get Tracker API instance
     *
     * @param string $scope
     * @param int $scopeId
     * @param string $apiKey
     * @return TrackerControllerApi
     * @throws ApiException
     * @throws ValidatorException
     */
    private function getTrackerApiInstance(string $scope, int $scopeId, string $apiKey): TrackerControllerApi
    {
        return $this->apiInstanceFactory->createApiInstance(
            'tracker',
            $this->configFactory->createConfigWithApiKey($apiKey, $scopeId, $scope)
        );
    }
}
