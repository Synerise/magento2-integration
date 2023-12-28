<?php

namespace Synerise\Integration\Setup;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;
use Magento\Config\Model\ResourceModel\Config\Data;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;


/**
 * @codeCoverageIgnore
 */
class Uninstall implements UninstallInterface
{
    const TABLES = [
        'synerise_workspace',
        'synerise_sync_subscriber',
        'synerise_sync_order',
        'synerise_sync_customer',
        'synerise_sync_product',
    ];

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;
    /**
     * @var Data
     */
    protected $configResource;

    /**
     * @param CollectionFactory $collectionFactory
     * @param Data $configResource
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        Data $configResource
    )
    {
        $this->collectionFactory = $collectionFactory;
        $this->configResource    = $configResource;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Exception
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        foreach (self::TABLES as $table) {
            if ($setup->tableExists($table)) {
                $setup->getConnection()->dropTable($table);
            }
        }
        $collection = $this->collectionFactory->create()
            ->addPathFilter('synerise');
        foreach ($collection as $config) {
            $this->deleteConfig($config);
        }
    }

    /**
     * @param AbstractModel $config
     * @throws \Exception
     */
    protected function deleteConfig(AbstractModel $config)
    {
        $this->configResource->delete($config);
    }
}