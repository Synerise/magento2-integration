<?php

namespace Synerise\Integration\Setup\Patch\Data;

use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ValueInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Store\Model\ScopeInterface;

Class ConfigMigration implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var CollectionFactory
     */
    private $configCollectionFactory;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * PatchInitial constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param WriterInterface $configWriter
     * @param CollectionFactory $configCollectionFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        WriterInterface $configWriter,
        CollectionFactory $configCollectionFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->configWriter = $configWriter;
        $this->configCollectionFactory = $configCollectionFactory;
    }

    public function apply()
    {
        $this->removeUnusedEavAttributes();
        $this->removeApiKeysAndsSetupEnabledModels();
    }

    public function getAliases()
    {
        return [];
    }

    public static function getDependencies()
    {
        return [];
    }

    protected function removeUnusedEavAttributes()
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        if ($eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, 'synerise_updated_at')) {
            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'synerise_updated_at');
        }
        if ($eavSetup->getAttributeId(\Magento\Customer\Model\Customer::ENTITY, 'synerise_updated_at')) {
            $eavSetup->removeAttribute(\Magento\Customer\Model\Customer::ENTITY, 'synerise_updated_at');
        }
    }

    protected function removeApiKeysAndsSetupEnabledModels()
    {
        $legacyConfig = [];
        $enabledModels = [];
        $collection = $this->configCollectionFactory->create()
            ->addPathFilter('synerise');

        foreach ($collection as $config) {
            if ($config->getScope() == ScopeConfigInterface::SCOPE_TYPE_DEFAULT) {
                if ($config->getPath() == 'synerise/api/key') {
                    $legacyConfig[] = $config;
                }

                $pathParts = explode('/', $config->getPath());
                if (isset($pathParts[2]) && $pathParts[2] == 'cron_enabled') {
                    $legacyConfig[] = $config;
                    if ($config->getValue()) {
                        $enabledModels[] = $pathParts[1];
                    }
                }
            } elseif ($config->getScope() == ScopeInterface::SCOPE_STORE) {
                if ($config->getPath() == 'synerise/api/key') {
                    $legacyConfig[] = $config;
                }
            }
        }

        if (!empty($enabledModels)) {
            $this->configWriter->save(
                'synerise/synchronization/models',
                implode(',', $enabledModels)
            );
        }

        if (!empty($legacyConfig)) {
            foreach($legacyConfig as $config) {
                $this->deleteConfig($config);
            }
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