<?php

namespace Synerise\Integration\Model\Config\Backend;

class BusinessProfile extends \Magento\Framework\App\Config\Value
{
    const XML_PATH_API_KEY = 'synerise/api/key';
    const XML_PATH_PROFILE_ID = 'synerise/business_profile/id';

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Synerise\Integration\Helper\Api $apiHelper,
        \Synerise\Integration\Model\BusinessProfile $businessProfile,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->configWriter = $configWriter;
        $this->apiHelper = $apiHelper;
        $this->businessProfile = $businessProfile;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function beforeSave()
    {
        $businessProfileId = (int) $this->getValue();
        if ($businessProfileId) {
            $businessProfile = $this->businessProfile->load($businessProfileId);
            $token = $this->getApiToken($businessProfile->getApiKey());

            $response = $this->apiHelper->getTrackerApiInstance($this->getScope(), $this->getScopeId(), $token)
                ->getOrCreateByDomain(new \Synerise\ApiClient\Model\TrackingCodeCreationByDomainRequest([
                    'domain' => $this->getConfigDomain() ?? $this->getBaseUrlDomain()
                ]));

            $this->configWriter->save(
                self::XML_PATH_API_KEY,
                $businessProfile->getData('api_key'),
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
        return $this->apiHelper->getApiToken($this->getScope(), $this->getScopeId(), $apiKey);
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
