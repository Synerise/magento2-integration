<?php

namespace Synerise\Integration\Setup\Patch\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\Integration\Model\AbstractSynchronization;

class DefaultStores implements DataPatchInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
    }

    public function apply()
    {
        if (!$this->scopeConfig->getValue(AbstractSynchronization::XML_PATH_SYNCHRONIZATION_STORES)) {
            $stores = $this->storeManager->getStores();
            $this->configWriter->save(
                AbstractSynchronization::XML_PATH_SYNCHRONIZATION_STORES,
                implode(',', array_keys($stores))
            );
        }
    }

    public function getAliases()
    {
        return [];
    }

    public static function getDependencies()
    {
        return [];
    }
}
