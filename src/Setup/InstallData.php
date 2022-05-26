<?php

namespace Synerise\Integration\Setup;

use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\Integration\Model\Cron\StatusFactory;

class InstallData implements InstallDataInterface
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StatusFactory
     */
    private $cronStatusFactory;

    public function __construct(
        EavSetupFactory $eavSetupFactory,
        Config $eavConfig,
        StatusFactory $cronStatusFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->eavSetupFactory      = $eavSetupFactory;
        $this->eavConfig            = $eavConfig;
        $this->storeManager         = $storeManager;
        $this->cronStatusFactory    = $cronStatusFactory;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $defaultStoreView = $this->storeManager->getDefaultStoreView();
        foreach (\Synerise\Integration\Helper\Config::MODELS as $model) {
            $this->cronStatusFactory
                ->create()
                ->setData([
                    'model' => $model,
                    'website_id' => $defaultStoreView->getWebsiteId(),
                    'store_id' => $defaultStoreView->getId()
                ])
                ->save();
        }
    }
}
