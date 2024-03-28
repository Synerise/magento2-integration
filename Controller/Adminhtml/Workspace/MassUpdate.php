<?php

namespace Synerise\Integration\Controller\Adminhtml\Workspace;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Ui\Component\MassAction\Filter;
use Synerise\Integration\Model\Workspace;
use Synerise\Integration\Model\ResourceModel\Workspace\CollectionFactory;
use Synerise\Integration\Model\Workspace\Validator;

class MassUpdate extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'Synerise_Integration::workspace';

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param Validator $validator
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        Validator $validator
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->validator = $validator;

        parent::__construct($context);
    }

    /**
     * Workspace delete action
     *
     * @return Redirect
     * @throws NotFoundException|LocalizedException
     */
    public function execute(): Redirect
    {
        if (!$this->getRequest()->isPost()) {
            throw new NotFoundException(__('Page not found'));
        }
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $updated = 0;

        /** @var Workspace $workspace */
        foreach ($collection->getItems() as $workspace) {
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
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__($e->getMessage()));
            }
            $updated++;
        }

        if ($updated) {
            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been updated.', $updated)
            );
        }
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }
}
