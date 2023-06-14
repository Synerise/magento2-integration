<?php
namespace Synerise\Integration\Ui\DataProvider\Workspace\Form;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Synerise\Integration\Model\ResourceModel\Workspace\CollectionFactory;
use Synerise\Integration\Model\Workspace;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var mixed
     */
    protected $session;
    /**
     * @var mixed
     */
    protected $loadedData;

    /**
     * @var mixed
     */
    protected $request;

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
        $this->request = $request ?? ObjectManager::getInstance()->get(RequestInterface::class);
        $this->collection = $workspaceCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }


    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $workspace = $this->getCurrentWorkspace();
        if($workspace->getId()) {
            $this->loadedData[$workspace->getId()]['api_key'] = $workspace->getApiKey();
            $this->loadedData[$workspace->getId()]['guid'] = $workspace->getGuid();
        }

        return $this->loadedData;
    }

    private function getCurrentWorkspace(): Workspace
    {
        $workspace = ObjectManager::getInstance()->create('Synerise\Integration\Model\Workspace');

        $workspaceId = $this->getWorkspaceId();
        if ($workspaceId) {
            try {
                $workspace->load($workspaceId);
            } catch (LocalizedException $exception) {
            }
        }

        return $workspace;
    }

    private function getWorkspaceId(): int
    {
        return (int) $this->request->getParam($this->getRequestFieldName());
    }

    protected function getSession()
    {
        if ($this->session === null) {
            $this->session = ObjectManager::getInstance()->get(
                \Magento\Framework\Session\SessionManagerInterface::class
            );
        }
        return $this->session;
    }

    /**
     * Get config data
     *
     * @return mixed
     */
    public function getConfigData()
    {
        $config = parent::getConfigData();
        $id = (int) $this->request->getParam($this->getRequestFieldName());
        if ($id) {
            $config['submit_url'] .= 'id/' . (int) $this->request->getParam($this->getRequestFieldName()) . '/';
        }
        return $config;
    }
}