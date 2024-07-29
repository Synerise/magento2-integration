<?php

namespace Synerise\Integration\Setup\Patch\Data;

use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Synerise\Integration\Model\Tracking\Config;

class ProductActionConfig implements DataPatchInterface
{
    const CONFIG_PATHS_TO_UPDATE = [
        Config::XML_PATH_EVENT_TRACKING_EVENTS
    ];
    const EVENTS_TO_ADD = [
        'catalog_product_action'
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
     * PatchInitial constructor.
     * @param WriterInterface $configWriter
     * @param CollectionFactory $configCollectionFactory
     */
    public function __construct(
        WriterInterface $configWriter,
        CollectionFactory $configCollectionFactory
    ) {
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
        $collection = $this->configCollectionFactory->create()
            ->addPathFilter('synerise');

        /** @var \Magento\Framework\App\Config\Value $config */
        foreach ($collection as $config) {
            if (in_array($config->getPath(), self::CONFIG_PATHS_TO_UPDATE)) {

                $value = explode(',', (string) $config->getValue());
                foreach (self::EVENTS_TO_ADD as $event) {
                    if (!in_array($event, $value)) {
                        $value[] = $event;
                    }
                }

                $this->configWriter->save(
                    $config->getPath(),
                    implode(',', $value),
                    $config->getScope(),
                    $config->getScopeId()
                );
            }
        }
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
