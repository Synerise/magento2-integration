<?php

namespace Synerise\Integration\Controller\Adminhtml\Workspace;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\ValidatorException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Model\Workspace;
use Synerise\Integration\Model\Workspace\Validator;

class Save extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'Synerise_Integration::workspace_add';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @param Context $context
     * @param Logger $logger
     * @param Validator $validator
     */
    public function __construct(
        Action\Context $context,
        Logger $logger,
        Validator $validator
    ) {
        $this->logger = $logger;
        $this->validator = $validator;

        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            /** @var Workspace $workspace */
            $workspace = $this->_objectManager->create(Workspace::class);
            $id = $this->getRequest()->getParam('id');
            if ($id) {
                $workspace->load($id);
            }

            $workspace
                ->setApiKey($data['api_key'])
                ->setEnvironment($data['environment'])
                ->setBasicAuthEnabled($data['basic_auth_enabled']);

            if (isset($data['guid'])) {
                $workspace->setGuid($data['guid']);
            }

            if (!$this->validator->isValid($workspace)) {
                foreach ($this->validator->getMessages() as $message) {
                    $this->messageManager->addErrorMessage($message);
                }
            } else {
                try {
                    $permissionCheck = $this->validator->checkPermissions($workspace);
                    $missingPermissions = [];
                    foreach ($permissionCheck->getPermissions() as $permission => $isSet) {
                        if (!$isSet) {
                            $missingPermissions[] = $permission;
                        }
                    }

                    $workspace
                        ->setName($permissionCheck->getBusinessProfileName())
                        ->setMissingPermissions(implode(PHP_EOL, $missingPermissions))
                        ->save();

                    $this->messageManager->addSuccessMessage(__('You saved this Workspace.'));
                    if ($this->getRequest()->getParam('back') == 'edit') {
                        return $resultRedirect->setPath('*/*/edit', ['id' => $workspace->getId(), '_current' => true]);
                    }
                    if ($this->getRequest()->getParam('back') == 'new') {
                        return $resultRedirect->setPath('*/*/new');
                    }
                    return $resultRedirect->setPath('*/*/');
                } catch (ValidatorException $e) {
                    $this->logger->getLogger()->error($e->getMessage());
                    $this->messageManager->addErrorMessage($e->getMessage());
                } catch (\Exception $e) {
                    $this->logger->getLogger()->error($e->getMessage());
                    $this->messageManager->addExceptionMessage(
                        $e,
                        __('Something went wrong while saving the Workspace.')
                    );
                }
            }
            return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}
