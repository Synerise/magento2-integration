<?php

namespace Synerise\Integration\Setup;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ValueInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Store\Model\StoreManagerInterface;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    public function __construct(
        EavSetupFactory $eavSetupFactory,
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        WriterInterface $configWriter
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        $this->configWriter = $configWriter;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            $setup->startSetup();

            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            if ($eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, 'synerise_updated_at')) {
                $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'synerise_updated_at');
            }
            if ($eavSetup->getAttributeId(\Magento\Customer\Model\Customer::ENTITY, 'synerise_updated_at')) {
                $eavSetup->removeAttribute(\Magento\Customer\Model\Customer::ENTITY, 'synerise_updated_at');
            }

            $enabledModels = [];
            $collection = $this->collectionFactory->create()
                ->addPathFilter('synerise');
            foreach ($collection as $config) {
                if ($config->getPath() == 'synerise/api/key') {
                    $this->deleteConfig($config);
                } elseif($config->getScope() == ScopeConfigInterface::SCOPE_TYPE_DEFAULT) {
                    $pathParts = explode('/', $config->getPath());
                    if (isset($pathParts[2]) && $pathParts[2] == 'cron_enabled') {
                        if ($config->getValue()) {
                            $enabledModels[] = $pathParts[1];
                        }
                    }
                }
            }

            if (!empty($enabledModels)) {
                $this->configWriter->save(
                    'synerise/synchronization/models',
                    implode(',', $enabledModels)
                );
            }

            $stores = $this->storeManager->getStores();
            $this->configWriter->save(
                'synerise/synchronization/stores',
                implode(',', array_keys($stores))
            );

            $setup->endSetup();
        }
    }

    /**
     * @param ValueInterface $config
     */
    protected function deleteConfig(ValueInterface $config)
    {
        $this->configWriter->delete(
            $config->getPath(),
            $config->getScope(),
            $config->getScopeId()
        );
    }
}


