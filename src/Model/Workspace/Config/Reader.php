<?php
namespace Synerise\Integration\Model\Workspace\Config;

use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigCollectionFactory;
use Magento\Framework\App\ScopeInterface;
use Magento\Framework\Config\ReaderInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;
use Magento\Store\Model\Website;
use Synerise\Integration\Model\ResourceModel\Workspace\CollectionFactory as WorkspaceCollectionFactory;
use Synerise\Integration\Model\Workspace;

class Reader implements ReaderInterface
{
    /**
     * @var ConfigCollectionFactory
     */
    protected $configCollectionFactory;

    /**
     * @var WebsiteCollectionFactory
     */
    protected $websiteCollectionFactory;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var WorkspaceCollectionFactory
     */
    protected $workspaceCollectionFactory;

    /**
     * @var array|null
     */
    protected $mapping;

    /**
     * @param ConfigCollectionFactory $configCollectionFactory
     * @param WebsiteCollectionFactory $websiteCollectionFactory
     * @param SerializerInterface $serializer
     * @param WorkspaceCollectionFactory $workspaceCollectionFactory
     */
    public function __construct(
        ConfigCollectionFactory $configCollectionFactory,
        WebsiteCollectionFactory $websiteCollectionFactory,
        SerializerInterface $serializer,
        WorkspaceCollectionFactory $workspaceCollectionFactory
    ) {
        $this->configCollectionFactory = $configCollectionFactory;
        $this->websiteCollectionFactory = $websiteCollectionFactory;
        $this->serializer = $serializer;
        $this->workspaceCollectionFactory = $workspaceCollectionFactory;
    }

    /**
     * Read configuration
     *
     * @param mixed $scope
     * @return array
     */
    public function read($scope = null): array
    {
        $output = [];

        $mapping = $this->getWorkspacesMapping();

        $websitesToLoad = [];
        foreach ($mapping as $websiteId => $workspaceId) {
            if ($workspaceId) {
                $websitesToLoad[] = $websiteId;
            }
        }

        $workspacesToLoad = array_unique($mapping);
        if ($workspacesToLoad && $websitesToLoad) {
            $workspaces = $this->workspaceCollectionFactory->create()
                ->addFieldToFilter('id', ['in' => $workspacesToLoad]);

            $websites = $this->websiteCollectionFactory->create()
                ->addFieldToFilter('website_id', ['in' => $websitesToLoad]);

            /** @var Website $website */
            foreach ($websites as $website) {
                $workspaceId = $mapping[$website->getId()] ?? null;
                if (!$workspaceId) {
                    continue;
                }

                /** @var Workspace $workspace */
                foreach ($workspaces as $workspace) {
                    if ($workspace->getId() == $workspaceId) {
                        $data = [
                            'apiKey' => $workspace->getApiKey(),
                            'guid' => $workspace->getGuid(),
                        ];

                        foreach ($website->getStores() as $store) {
                            $output[$store->getId()] = $data;
                        }
                    }
                }
            }
        }

        return $output;
    }

    /**
     * Get an array of workspace to website mapping
     *
     * @return array
     */
    protected function getWorkspacesMapping()
    {
        if (empty($this->mapping)) {
            $this->mapping = [];
            $collection = $this->configCollectionFactory->create()
                ->addFieldToFilter('path', Workspace::XML_PATH_WORKSPACE_MAP)
                ->addFieldToFilter('scope', ScopeInterface::SCOPE_DEFAULT);

            foreach ($collection->getItems() as $item) {
                $this->mapping = $this->serializer->unserialize($item->getValue());
            }
        }

        return $this->mapping;
    }
}
