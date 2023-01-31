<?php

namespace Synerise\Integration\Model\Config\Backend;

use Magento\Framework\Exception\LocalizedException;
use Synerise\ApiClient\ApiException;

class Workspace extends \Magento\Framework\App\Config\Value
{
    const XML_PATH_API_KEY = 'synerise/api/key';
    const XML_PATH_WORKSPACE_ID = 'synerise/workspace/id';
    const ERROR_MSG_403 = 'Please make sure this api key has all required roles.';

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Synerise\Integration\Helper\Api $apiHelper,
        \Synerise\Integration\Model\Workspace $workspace,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->configWriter = $configWriter;
        $this->apiHelper = $apiHelper;
        $this->workspace = $workspace;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function beforeSave()
    {
        $workspaceId = (int) $this->getValue();
        if ($workspaceId) {
            $workspace = $this->workspace->load($workspaceId);
            $token = $this->getApiToken($workspace->getApiKey());

            try {
                $response = $this->apiHelper->getTrackerApiInstance($this->getScope(), $this->getScopeId(), $token)
                    ->getOrCreateByDomain(new \Synerise\ApiClient\Model\TrackingCodeCreationByDomainRequest([
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

            $this->configWriter->save(
                \Synerise\Integration\Helper\Tracking::XML_PATH_PAGE_TRACKING_KEY,
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
                \Synerise\Integration\Helper\Tracking::XML_PATH_PAGE_TRACKING_KEY,
                $this->getScope(),
                $this->getScopeId()
            );
        }

        parent::beforeSave();
    }

    protected function getApiToken($apiKey)
    {
        return $this->apiHelper->getApiToken($this->getScope(), $this->getScopeId(), null, $apiKey);
    }

    private function getConfigDomain()
    {
        $domain = $this->_config->getValue(
            \Synerise\Integration\Helper\Tracking::XML_PATH_PAGE_TRACKING_DOMAIN,
            $this->getScope(),
            $this->getScopeId()
        );

        return $domain ? trim($domain, '.') : null;
    }

    private function getBaseUrlDomain()
    {
        $baseUrl = $this->_config->getValue(
            \Magento\Store\Model\Store::XML_PATH_UNSECURE_BASE_LINK_URL,
            $this->getScope(),
            $this->getScopeId()
        );

        $parsedUrl = parse_url($baseUrl);
        return $parsedUrl['host'];
    }
}
