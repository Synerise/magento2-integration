<?php

namespace Synerise\Integration\Model\Synchronization\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Synerise\Integration\Model\Synchronization\ConfigInterface;

class Order implements ConfigInterface
{
    const XML_PATH_ORDER_PAGE_SIZE = 'synerise/order/limit';


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
     * @return array
     */
    public function getAttributesToSelect(int $storeId)
    {
        return [];
    }

    /**
     * @param int|null $storeId
     * @return int
     */
    public function getPageSize(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_ORDER_PAGE_SIZE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}