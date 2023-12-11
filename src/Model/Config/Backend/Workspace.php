<?php

namespace Synerise\Integration\Model\Config\Backend;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\ValidatorException;
use Synerise\ApiClient\Api\TrackerControllerApi;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\TrackingCodeCreationByDomainRequest;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\SyneriseApi\ConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory;

class Workspace extends \Magento\Framework\App\Config\Value
{
    const XML_PATH_API_KEY = 'synerise/api/key';
    const XML_PATH_API_BASIC_TOKEN = 'synerise/api/basic_token';
    const ERROR_MSG_403 = 'Please make sure this api key has all required roles.';

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    protected $configWriter;

    /**
     * @var \Synerise\Integration\Model\Workspace
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

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Synerise\Integration\Model\Workspace $workspace,
        ConfigFactory $configFactory,
        InstanceFactory $apiInstanceFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
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
     * @throws ApiException
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $workspaceId = (int) $this->getValue();
        if ($workspaceId) {
            $workspace = $this->workspace->load($workspaceId);

            try {
                $response = $this->getTrackerApiInstance($this->getScope(), $this->getScopeId(), $workspace->getApiKey())
                    ->getOrCreateByDomain(new TrackingCodeCreationByDomainRequest([
                        'domain' => $this->getConfigDomain() ?? $this->getBaseUrlDomain()
                    ]));
            } catch (ApiException $e) {
                if ($e->getCode() == 403) {
                    throw new LocalizedException(__(self::ERROR_MSG_403));
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
                Tracking::XML_PATH_PAGE_TRACKING_KEY,
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
                Tracking::XML_PATH_PAGE_TRACKING_KEY,
                $this->getScope(),
                $this->getScopeId()
            );
        }

        parent::beforeSave();
    }

    /**
     * @return string|null
     */
    private function getConfigDomain(): ?string
    {
        $domain = $this->_config->getValue(
            Tracking::XML_PATH_PAGE_TRACKING_DOMAIN,
            $this->getScope(),
            $this->getScopeId()
        );

        return $domain ? trim($domain, '.') : null;
    }

    /**
     * @return string
     */
    private function getBaseUrlDomain(): string
    {
        $baseUrl = $this->_config->getValue(
            \Magento\Store\Model\Store::XML_PATH_UNSECURE_BASE_LINK_URL,
            $this->getScope(),
            $this->getScopeId()
        );

        $parsedUrl = parse_url($baseUrl);
        return $parsedUrl['host'];
    }

    /**
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
