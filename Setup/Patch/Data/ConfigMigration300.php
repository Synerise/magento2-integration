<?php

namespace Synerise\Integration\Setup\Patch\Data;

use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ValueInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\Integration\Block\Tracking\Code;
use Synerise\Integration\Helper\Logger;

class ConfigMigration300 implements DataPatchInterface
{
    protected const CONFIG_PATHS_TO_DELETE = [
        'synerise/api/key',
        'synerise/api/host',
        'synerise/api/basic_token',
        'synerise/api/basic_auth_enabled',
        'synerise/workspace/id',
        'synerise/cron_queue/enabled',
        'synerise/cron_queue/expr',
        'synerise/cron_queue/page_size',
        'synerise/cron_status/enabled',
        'synerise/cron_status/expr',
        'synerise/cron_status/page_size',
        'synerise/queue/connection'
    ];

    /**
     * @var CollectionFactory
     */
    private $configCollectionFactory;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * PatchInitial constructor.
     * @param StoreManagerInterface $storeManager
     * @param WriterInterface $configWriter
     * @param CollectionFactory $configCollectionFactory
     * @param Logger $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        WriterInterface $configWriter,
        CollectionFactory $configCollectionFactory,
        Logger $logger
    ) {
        $this->storeManager = $storeManager;
        $this->configWriter = $configWriter;
        $this->configCollectionFactory = $configCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * Apply
     *
     * @return void
     */
    public function apply()
    {
        $this->updateTrackerKeysScope();
        $this->removeLegacyConfig();
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

    /**
     * Update scope of tracker keys
     *
     * @return void
     */
    private function updateTrackerKeysScope()
    {
        $collection = $this->configCollectionFactory->create()
            ->addFieldToFilter('path', Code::XML_PATH_PAGE_TRACKING_KEY)
            ->addFieldToFilter('scope', ScopeInterface::SCOPE_WEBSITES);

        /** @var ValueInterface $value */
        foreach ($collection as $value) {
            try {
                foreach ($this->storeManager->getWebsite($value->getScopeId())->getStoreIds() as $storeId) {
                    $this->configWriter->save(
                        Code::XML_PATH_PAGE_TRACKING_KEY,
                        $value->getValue(),
                        ScopeInterface::SCOPE_STORES,
                        $storeId
                    );
                }
                $this->deleteConfig($value);
            } catch (LocalizedException $e) {
                $this->logger->error($e);
            }
        }
    }

    /**
     * Remove legacy config
     *
     * @return void
     */
    private function removeLegacyConfig()
    {
        $collection = $this->configCollectionFactory->create()
            ->addFieldToFilter('path', ['in' => self::CONFIG_PATHS_TO_DELETE]);

        foreach ($collection as $value) {
            $this->deleteConfig($value);
        }
    }
}
