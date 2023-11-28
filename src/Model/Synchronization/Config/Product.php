<?php

namespace Synerise\Integration\Model\Synchronization\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Synerise\Integration\Model\Config\Source\Products\Attributes;
use Synerise\Integration\Model\Synchronization\ConfigInterface;

class Product implements ConfigInterface
{
    const XML_PATH_PRODUCT_ATTRIBUTES = 'synerise/product/attributes';

    const XML_PATH_PRODUCT_PAGE_SIZE = 'synerise/product/limit';


    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ){
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param int $storeId
     * @return array|false|string[]
     */
    public function getEnabledAttributes(int $storeId)
    {
        $attributes = $this->scopeConfig->getValue(
            self::XML_PATH_PRODUCT_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return !empty($attributes) ? explode(',', $attributes) : [];
    }

    /**
     * @param int $storeId
     * @return array|false|string[]
     */
    public function getAttributesToSelect(int $storeId)
    {
        return array_merge(
            $this->getEnabledAttributes($storeId),
            Attributes::REQUIRED
        );
    }

    /**
     * @param int|null $storeId
     * @return int
     */
    public function getPageSize(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_PRODUCT_PAGE_SIZE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}