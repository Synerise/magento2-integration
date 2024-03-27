<?php
namespace Synerise\Integration\Ui\DataProvider\Workspace\Form;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Synerise\Integration\Model\ResourceModel\Workspace\CollectionFactory;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var mixed
     */
    protected $loadedData;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $workspaceCollectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $workspaceCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $workspaceCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritDoc
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        foreach ($this->getCollection() as $workspace) {
            $this->loadedData[$workspace->getId()]['id'] = $workspace->getId();
            $this->loadedData[$workspace->getId()]['environment'] = $workspace->getEnvironment();
            $this->loadedData[$workspace->getId()]['api_key'] = $workspace->getApiKey();
            $this->loadedData[$workspace->getId()]['basic_auth_enabled'] = $workspace->getBasicAuthEnabled();
            $this->loadedData[$workspace->getId()]['guid'] = $workspace->getGuid();
        }

        return $this->loadedData;
    }
}
