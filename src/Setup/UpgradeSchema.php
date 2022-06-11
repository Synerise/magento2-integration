<?php
namespace Synerise\Integration\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements \Magento\Framework\Setup\UpgradeSchemaInterface
{

    /**
     * @var InstallSchema
     */
    private $installSchema;

    public function __construct(InstallSchema $installSchema)
    {
        $this->installSchema = $installSchema;
    }

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->installSchema->install($setup, $context);
    }
}
