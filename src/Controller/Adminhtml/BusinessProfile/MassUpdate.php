<?php

namespace Synerise\Integration\Controller\Adminhtml\BusinessProfile;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Ui\Component\MassAction\Filter;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\ApiKeyPermissionCheckResponse;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Model\BusinessProfile;
use Synerise\Integration\ResourceModel\BusinessProfile\CollectionFactory;


class MassUpdate extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Synerise_Integration::delete';

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Filter
     */
    protected $filter;
    /**
     * @var Api
     */
    protected $apiHelper;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param Api $apiHelper
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        Api $apiHelper
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->apiHelper = $apiHelper;

        parent::__construct($context);
    }

    /**
     * Business Profile delete action
     *
     * @return Redirect
     * @throws NotFoundException
     * @throws LocalizedException
     */
    public function execute(): Redirect
    {
        if (!$this->getRequest()->isPost()) {
            throw new NotFoundException(__('Page not found'));
        }
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $updated = 0;

        /** @var BusinessProfile $businessProfile */
        foreach ($collection->getItems() as $businessProfile) {
            try {
                $this->update($businessProfile);
            } catch (\Exception $e) {
                $this->messageManager->addError(__($e->getMessage()));
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

    /**
     * @param $businessProfile
     * @throws ValidatorException
     * @throws ApiException
     */
    protected function update($businessProfile)
    {
        $permissionCheck = $this->checkPermissions($businessProfile->getApiKey());
        $missingPermissions = [];
        $permissions = $permissionCheck->getPermissions();
        foreach ($permissions as $permission => $isSet) {
            if (!$isSet) {
                $missingPermissions[] = $permission;
            }
        }

        $businessProfile
            ->setName($permissionCheck->getBusinessProfileName())
            ->setMissingPermissions(implode(PHP_EOL, $missingPermissions))
            ->save();
    }

    /**
     * @param string $apiKey
     * @param string $scope
     * @param null $scopeId
     * @return ApiKeyPermissionCheckResponse
     * @throws ValidatorException
     * @throws ApiException
     */
    protected function checkPermissions($apiKey, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = null)  {
        $token = $this->apiHelper->getApiToken($scope, $scopeId, $apiKey);

        return $this->apiHelper->getApiKeyApiInstance($scope, $scopeId, $token)
            ->checkPermissions(BusinessProfile::REQUIRED_PERMISSIONS);
    }
}