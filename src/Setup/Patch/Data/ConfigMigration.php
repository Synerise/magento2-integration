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
use Synerise\Integration\Model\Config\Backend\Workspace;
use Synerise\Integration\Model\Synchronization\Config;

class ConfigMigration implements DataPatchInterface
{
    protected const CONFIG_PATHS_TO_DELETE = [
        'synerise/product/cron_enabled',
        'synerise/customer/cron_enabled',
        'synerise/order/cron_enabled',
        'synerise/subscriber/cron_enabled',
        'synerise/cron_status/enabled',
        'synerise/cron_queue/enabled'
    ];
    public const XML_PATH_API_KEY = 'synerise/api/key';

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

    /**
     * Apply
     *
     * @return void
     */
    public function apply()
    {
        $this->removeUnusedEavAttributes();
        $this->removeLegacyConfigAndSetupEnabledModels();
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Remove EAV attributes
     *
     * @return void
     */
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

    /**
     * Remove legacy config and enable all models by default
     *
     * @return void
     */
    protected function removeLegacyConfigAndSetupEnabledModels()
    {
        $configToDelete = [];
        $enabledModels = [];
        $collection = $this->configCollectionFactory->create()
            ->addPathFilter('synerise');

        $cronQueueEnabled = $synchronizationEnabled = false;

        foreach ($collection as $config) {
            if (in_array($config->getPath(), self::CONFIG_PATHS_TO_DELETE)) {
                $configToDelete[] = $config;

                if ($config->getScope() == ScopeConfigInterface::SCOPE_TYPE_DEFAULT) {
                    $pathParts = explode('/', $config->getPath());
                    if ($config->getValue()) {
                        $enabledModels[] = $pathParts[1];
                    }
                }
            } elseif ($config->getPath() == self::XML_PATH_API_KEY &&
                $config->getScope() != ScopeInterface::SCOPE_WEBSITES) {
                $configToDelete[] = $config;
            } elseif ($config->getPath() == 'synerise/synchronization/enabled') {
                $synchronizationEnabled = true;
            } elseif ($config->getPath() == 'synerise/cron_queue/enabled') {
                $cronQueueEnabled = $config->getValue();
            }
        }

        if (!empty($enabledModels)) {
            $this->configWriter->save(
                Config::XML_PATH_SYNCHRONIZATION_MODELS,
                implode(',', $enabledModels)
            );
        }

        if (!empty($configToDelete)) {
            foreach ($configToDelete as $config) {
                $this->deleteConfig($config);
            }
        }

        if ($cronQueueEnabled && !$synchronizationEnabled) {
            $this->configWriter->save(
                'synerise/synchronization/enabled',
                1
            );
        }
    }

    /**
     * Delete config
     *
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
