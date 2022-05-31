<?php

namespace Synerise\Integration\Setup;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(
        EavSetupFactory $eavSetupFactory,
        \Psr\Log\LoggerInterface $logger

    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->logger = $logger;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.1.1', '>')) {
            $setup->startSetup();

            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            if($eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, 'synerise_updated_at')) {
                $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'synerise_updated_at');
            }
            if($eavSetup->getAttributeId(\Magento\Customer\Model\Customer::ENTITY, 'synerise_updated_at')) {
                $eavSetup->removeAttribute(\Magento\Customer\Model\Customer::ENTITY, 'synerise_updated_at');
            }

            $setup->endSetup();
        }
    }
}


