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
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $eavSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            'synerise_updated_at',
            [
                'type' => 'datetime',
                'label' => 'Synerise Updated At',
                'input' => 'date',
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_searchable_in_grid' => true,
                'frontend' => \Magento\Eav\Model\Entity\Attribute\Frontend\Datetime::class,
                'backend' => \Magento\Eav\Model\Entity\Attribute\Backend\Datetime::class,
                'required' => false,
                'sort_order' => 90,
                'user_defined' => false,
                'visible' => true,
                'system' => true,
                'input_filter' => 'date'
            ]
        );

        $this->eavConfig->getAttribute('customer', 'synerise_updated_at')
            ->setData('is_user_defined', 0)
            ->setData('is_required', 0)
            ->setData('used_in_forms', ['adminhtml_customer'])
            ->save();


        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'synerise_updated_at',
            [
                'type' => 'datetime',
                'label' => 'Synerise Updated At',
                'input' => 'date',
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_searchable_in_grid' => true,
                'frontend' => \Magento\Eav\Model\Entity\Attribute\Frontend\Datetime::class,
                'backend' => \Magento\Eav\Model\Entity\Attribute\Backend\Datetime::class,
                'required' => false,
                'sort_order' => 90,
                'user_defined' => false,
                'visible' => true,
                'system' => true,
                'input_filter' => 'date'
            ]
        );

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
