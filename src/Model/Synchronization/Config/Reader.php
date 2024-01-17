<?php

namespace Synerise\Integration\Model\Synchronization\Config;

use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\App\ScopeInterface;
use Magento\Framework\Config\ReaderInterface;
use Synerise\Integration\Model\Workspace\ConfigFactory as WorkspaceConfigFactory;

class Reader implements ReaderInterface
{
    public const SECTION_PATH = 'synerise/synchronization';

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var WorkspaceConfigFactory
     */
    private $workspaceConfigFactory;

    /**
     * @param CollectionFactory $collectionFactory
     * @param WorkspaceConfigFactory $workspaceConfigFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        WorkspaceConfigFactory $workspaceConfigFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->workspaceConfigFactory = $workspaceConfigFactory;
    }

    /**
     * @inheritDoc
     */
    public function read($scope = null)
    {
        $output = [];

        foreach ($this->getConfigSection() as $item) {
            $path = preg_replace(sprintf("@%s/@", self::SECTION_PATH), '', $item->getPath());
            if ($path == 'enabled') {
                $output[$path] = (bool) $item->getValue();
            } else {
                $output[$path] =  explode(',', $item->getValue());
            }
        }

        if (isset($output['stores'])) {
            $output['stores_configured'] = $this->getConfiguredStores($output['stores']);
        }

        $output['limit'] = $this->getLimitArray();

        return $output;
    }

    /**
     * Get synchronization config data
     *
     * @return array
     */
    private function getConfigSection(): array
    {
        return $this->collectionFactory->create()
            ->addScopeFilter(ScopeInterface::SCOPE_DEFAULT, 0, self::SECTION_PATH)
            ->getItems();
    }

    /**
     * Get limits configuration
     *
     * @return array
     */
    private function getLimitArray(): array
    {
        $collection = $this->collectionFactory->create()
             ->addFieldToFilter('path', ['like' => 'synerise/%/limit'])
             ->addFieldToFilter('scope', ScopeInterface::SCOPE_DEFAULT);

        $limits = [];
        foreach ($collection->getItems() as $item) {
            $pathSegments = explode('/', $item->getPath());
            $limits[$pathSegments[1]] = $item->getValue();
        }
        return $limits;
    }

    /**
     * Get array of store IDs with valid config
     *
     * @param array $stores
     * @return array
     */
    private function getConfiguredStores(array $stores): array
    {
        $configured = [];
        foreach ($stores as $storeId) {
            if ($this->workspaceConfigFactory->create($storeId)->isApiKeySet()) {
                $configured[] = $storeId;
            }
        }
        return $configured;
    }
}
