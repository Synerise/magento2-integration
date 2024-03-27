<?php

namespace Synerise\Integration\Model\Config\Backend\Tracking;

use Exception;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Validator\ValidateException;
use Magento\Store\Model\Store;

class Domain extends Value
{
    /**
     * Validate domain
     *
     * @return Domain
     * @throws ValidateException
     */
    public function beforeSave()
    {
        if (!$this->isValueChanged() || !$this->getValue()) {
            return parent::beforeSave();
        }

        $baseUrlDomain = $this->getBaseUrlDomain();
        $valueTrimmed = preg_replace('/^(http(s)?:\/\/)?((www)?\.)/', '', $this->getValue());

        if (!$baseUrlDomain || strpos($baseUrlDomain, $valueTrimmed) === false) {
            throw new ValidateException(sprintf('Domain should be a substring of base url (%s)', $baseUrlDomain));
        }

        $this->setValue(".$valueTrimmed");

        return parent::beforeSave();
    }

    /**
     * Get base url domain
     *
     * @return string|null
     */
    private function getBaseUrlDomain(): ?string
    {
        $baseUrl = $this->_config->getValue(
            Store::XML_PATH_UNSECURE_BASE_LINK_URL,
            $this->getScope(),
            $this->getScopeId()
        );

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $parsedUrl = parse_url($baseUrl);
        return $parsedUrl['host'] ?? null;
    }
}
