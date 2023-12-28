<?php

namespace Synerise\Integration\Model\Config\Backend\Tracking;

class Domain extends \Magento\Framework\App\Config\Value
{
    public function beforeSave()
    {
        if (!$this->isValueChanged() || !$this->getValue()) {
            return parent::beforeSave();
        }

        $baseUrlDomain = $this->getBaseUrlDomain();
        $valueTrimmed = preg_replace('/^(http(s)?:\/\/)?((www)?\.)/', '', $this->getValue());

        if (!$baseUrlDomain || strpos($baseUrlDomain, $valueTrimmed) === false) {
            throw new \Exception(sprintf('Domain should be a substring of base url (%s)', $baseUrlDomain));
        }

        $this->setValue(".$valueTrimmed");

        return parent::beforeSave();
    }

    private function getBaseUrlDomain()
    {
        $baseUrl = $this->_config->getValue(
            \Magento\Store\Model\Store::XML_PATH_UNSECURE_BASE_LINK_URL,
            $this->getScope(),
            $this->getScopeId()
        );

        $parsedUrl = parse_url($baseUrl);
        return isset($parsedUrl['host']) ? $parsedUrl['host'] : null;
    }
}
