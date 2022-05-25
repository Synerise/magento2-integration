<?php
namespace Synerise\Integration\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context ) {
        $installer = $setup;

        $installer->startSetup();

        if(version_compare($context->getVersion(), '1.2.0', '<')) {
            $installer = $setup;
            $installer->startSetup();
            if (!$installer->tableExists('synerise_business_profile')) {
                $table = $installer->getConnection()->newTable(
                    $installer->getTable('synerise_business_profile')
                )
                    ->addColumn(
                        'id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        null,
                        [
                            'identity' => true,
                            'nullable' => false,
                            'primary'  => true,
                            'unsigned' => true,
                        ],
                        'Business Profile ID'
                    )
                    ->addColumn(
                        'name',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        100,
                        ['nullable => false'],
                        'Business Profile Name'
                    )
                    ->addColumn(
                        'api_key',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        100,
                        ['nullable => false'],
                        'Business Profile Api Key'
                    )
                    ->addColumn(
                        'uuid',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        36,
                        ['nullable => false'],
                        'Business Profile Unique Identifier'
                    )
                    ->addColumn(
                        'missing_permissions',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        255,
                        [],
                        'Missing Required Permissions'
                    )
                    ->addColumn(
                        'created_at',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        null,
                        ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                        'Created At'
                    )->addColumn(
                        'updated_at',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        null,
                        ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
                        'Updated At')
                    ->setComment('Business Profile Table');
                $installer->getConnection()->createTable($table);

                $installer->getConnection()->addIndex(
                    $installer->getTable('synerise_business_profile'),
                    $setup->getIdxName(
                        $installer->getTable('synerise_business_profile'),
                        ['uuid'],
                        \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                    ),
                    ['uuid'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                );
            }
            $installer->endSetup();
        }

        $installer->endSetup();
    }
}
