<?php

namespace Synerise\Integration\Controller\Adminhtml\Workspace;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\ValidatorException;
use Synerise\ApiClient\Api\ApiKeyControllerApi;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\ApiKeyPermissionCheckResponse;
use Synerise\Integration\Model\Workspace;
use Synerise\Integration\SyneriseApi\ConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory;

class Save extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'Synerise_Integration::workspace_add';

    /**
     * @var ConfigFactory
     */
    private $configFactory;

    /**
     * @var InstanceFactory
     */
    private $apiInstanceFactory;

    /**
     * @param Context $context
     * @param ConfigFactory $configFactory
     * @param InstanceFactory $apiInstanceFactory
     */
    public function __construct(
        Action\Context $context,
        ConfigFactory $configFactory,
        InstanceFactory $apiInstanceFactory
    ) {
        $this->configFactory = $configFactory;
        $this->apiInstanceFactory = $apiInstanceFactory;

        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            /** @var Workspace $workspace */
            $workspace = $this->_objectManager->create(Workspace::class);
            $id = $this->getRequest()->getParam('id');
            if ($id) {
                $workspace->load($id);
            }

            if (isset($data['api_key'])) {
                $apiKey = $data['api_key'];
                $workspace->setApiKey($apiKey);

                if (isset($data['guid'])) {
                    $workspace->setGuid($data['guid']);
                }
            } else {
                $apiKey = $workspace->getApiKey();
            }

            try {

                if (!$apiKey) {
                    throw new LocalizedException(__('Missing api key'));
                }

                $permissionCheck = $this->checkPermissions($workspace);
                $missingPermissions = [];
                $permissions = $permissionCheck->getPermissions();
                foreach ($permissions as $permission => $isSet) {
                    if (!$isSet) {
                        $missingPermissions[] = $permission;
                    }
                }

                $workspace
                    ->setName($permissionCheck->getBusinessProfileName())
                    ->setMissingPermissions(implode(PHP_EOL, $missingPermissions))
                    ->save();

                $this->messageManager->addSuccess(__('You saved this Workspace.'));
                $this->_objectManager->get(\Magento\Backend\Model\Session::class)->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $workspace->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the Workspace.'));
            }

            $this->_getSession()->setFormData($data);
            return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Check permissions
     *
     * @param Workspace $workspace
     * @param string $scope
     * @param int|null $scopeId
     * @return ApiKeyPermissionCheckResponse
     * @throws ApiException|ValidatorException
     */
    protected function checkPermissions(
        Workspace $workspace,
        string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        ?int $scopeId = null
    ): ApiKeyPermissionCheckResponse {
        return $this->createApiKeyInstance($workspace, $scope, $scopeId)
            ->checkPermissions(Workspace::REQUIRED_PERMISSIONS);
    }

    /**
     * Create API key instance
     *
     * @param Workspace $workspace
     * @param string $scope
     * @param int|null $scopeId
     * @return ApiKeyControllerApi
     * @throws ApiException|ValidatorException
     */
    protected function createApiKeyInstance(
        Workspace $workspace,
        string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        ?int $scopeId = null
    ): ApiKeyControllerApi {
        return $this->apiInstanceFactory->createApiInstance(
            'apiKey',
            $this->configFactory->create($scopeId, $scope),
            $workspace
        );
    }
}
