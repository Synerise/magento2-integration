<?php
namespace Synerise\Integration\Ui\DataProvider\Workspace\Form;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Synerise\Integration\Model\ResourceModel\Workspace\CollectionFactory;
use Synerise\Integration\Model\Workspace;

class DataProvider extends AbstractDataProvider
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
     * @inheritDoc
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $workspace = $this->getCurrentWorkspace();
        if ($workspace->getId()) {
            $this->loadedData[$workspace->getId()]['api_key'] = $workspace->getApiKey();
            $this->loadedData[$workspace->getId()]['guid'] = $workspace->getGuid();
        }

        return $this->loadedData;
    }

    /**
     * Get current workspace
     *
     * @return Workspace
     */
    private function getCurrentWorkspace(): Workspace
    {
        $workspace = ObjectManager::getInstance()->create(Workspace::class);
        $workspaceId = $this->getWorkspaceId();
        if ($workspaceId) {
            $workspace->load($workspaceId);
        }

        return $workspace;
    }

    /**
     * Ger workspace id from params
     *
     * @return int
     */
    private function getWorkspaceId(): int
    {
        return (int) $this->request->getParam($this->getRequestFieldName());
    }

    /**
     * Get session manager
     *
     * @return SessionManagerInterface
     */
    protected function getSession(): SessionManagerInterface
    {
        if ($this->session === null) {
            $this->session = ObjectManager::getInstance()->get(SessionManagerInterface::class);
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
