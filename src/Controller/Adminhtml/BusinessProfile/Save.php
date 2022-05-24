<?php

namespace Synerise\Integration\Controller\Adminhtml\BusinessProfile;

use Magento\Backend\App\Action;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\TestFramework\ErrorLog\Logger;
use Synerise\Integration\Model\BusinessProfile;

class Save extends \Magento\Backend\App\Action
{

    /**
     * @param Action\Context $context
     */
    public function __construct
    (
        Action\Context $context,
        \Synerise\Integration\Helper\Api $apiHelper
    ) {
        $this->apiHelper = $apiHelper;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Synerise_Integration::save');
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            /** @var \Synerise\Integration\Model\BusinessProfile $model */
            $model = $this->_objectManager->create('Synerise\Integration\Model\BusinessProfile');
            $id = $this->getRequest()->getParam('id');
            if ($id) {
                $model->load($id);
            }

            $permissionCheck = $this->checkPermissions($data['api_key']);

            $model->setName($permissionCheck->getBusinessProfileName());
            $model->setApiKey($data['api_key']);

            $permissions = $permissionCheck->getPermissions();

            try {
                $model->save();
                $this->messageManager->addSuccess(__('You saved this Business Profile.'));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the Business Profile.'));
            }

            $this->_getSession()->setFormData($data);
            return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * @param $apiKey
     * @param string $scope
     * @param null $scopeId
     * @return \Synerise\ApiClient\Model\ApiKeyPermissionCheckResponse
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \Synerise\ApiClient\ApiException
     */
    protected function checkPermissions($apiKey, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = null)  {
        $token = $this->apiHelper->getApiToken($scope, $scopeId, $apiKey);

        return $this->apiHelper->getApiKeyApiInstance($scope, $scopeId, $token)
            ->checkPermissions(BusinessProfile::REQUIRED_PERMISSIONS);
    }
}