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

class CronCleanUp implements DataPatchInterface
{
    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * PatchInitial constructor.
     * @param WriterInterface $configWriter
     */
    public function __construct(
        WriterInterface $configWriter,
    ) {
        $this->configWriter = $configWriter;
    }

    /**
     * Apply
     *
     * @return void
     */
    public function apply()
    {
        $paths = [
            'crontab/default/jobs/synerise_sync_status/schedule/cron_expr',
            'crontab/default/jobs/synerise_sync_status/run/model',
            'crontab/default/jobs/synerise_sync_queue/schedule/cron_expr',
            'crontab/default/jobs/synerise_sync_queue/run/model'
        ];

        foreach($paths as $path) {
            $this->deleteConfig($path);
        }
    }


    /**
     * Delete config
     *
     * @param string $path
     * @param string $scope
     * @param int $scopeId
     */
    protected function deleteConfig(
        string $path,
        string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        int $scopeId = 0
    ) {
        $this->configWriter->delete(
            $path,
            $scope,
            $scopeId
        );
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
}
