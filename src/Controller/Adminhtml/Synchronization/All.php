<?php
namespace Synerise\Integration\Controller\Adminhtml\Synchronization;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\MessageQueue\CollectionFactoryProvider;
use Synerise\Integration\MessageQueue\Filter;
use Synerise\Integration\MessageQueue\Publisher\Data\Scheduler as Publisher;
use Synerise\Integration\Model\Config\Source\Synchronization\Model;

class All extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Synerise_Integration::synchronization';

    /**
     * @var Publisher
     */
    protected $publisher;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactoryProvider
     */
    protected $collectionFactoryProvider;

    /**
     * @var Synchronization
     */
    protected $synchronization;

    public function __construct(
        Context $context,
        Publisher $publisher,
        Filter $filter,
        CollectionFactoryProvider $collectionFactoryProvider,
        Synchronization $synchronization
    ) {
        $this->publisher = $publisher;
        $this->filter = $filter;
        $this->collectionFactoryProvider = $collectionFactoryProvider;
        $this->synchronization = $synchronization;

        parent::__construct($context);
    }

    public function execute()
    {
        try {
            if (!$this->synchronization->isSynchronizationEnabled()) {
                $this->messageManager->addErrorMessage(
                    __('Synchronization is disabled. Please review your configuration.')
                );
            } else {
                $selectedStoreIds = $this->getSelectedStoreIds($this->getRequest()->getParam('store'));
                $selectedModels = $this->getSelectedModels($this->getRequest()->getParam('selected'));

                foreach($selectedModels as $model) {
                    if (!$this->synchronization->isEnabledModel($model)) {
                        $this->messageManager->addErrorMessage(
                            __('%1s are excluded from synchronization.', ucfirst($model))
                        );
                    } else {
                        $storeIdsWithItems = [];

                        foreach ($selectedStoreIds as $storeId) {
                            if ($this->synchronization->isEnabledStore($storeId)) {
                                if ($this->storeHasItems($model, $storeId)) {
                                    $storeIdsWithItems[] = $storeId;
                                }
                            }
                        }

                        if (!empty($storeIdsWithItems)) {
                            $this->publisher->schedule(
                                $model,
                                $storeIdsWithItems
                            );
                            $this->messageManager->addSuccessMessage(
                                __(
                                    '%1 synchronization has been scheduled for stores: %2',
                                    ucfirst($model),
                                    implode(',', $storeIdsWithItems)
                                )
                            );
                        } else {
                            $this->messageManager->addErrorMessage(
                                __(
                                    'No %1s to synchronize for selected stores: %2.',
                                    $model,
                                    implode(',', $selectedStoreIds)
                                )
                            );
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Something went wrong while processing the request.'));
        }

        $params = [];
        if ($this->getRequest()->getParam('store')) {
            $params['store'] = $this->getRequest()->getParam('store');
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/index', $params);
    }

    /**
     * @param string $model
     * @param int $storeId
     * @return bool
     */
    protected function storeHasItems(string $model, int $storeId): bool
    {
        $collection = $this->filter->addStoreFilter(
            $this->collectionFactoryProvider->get($model)->create(),
            $storeId
        )->setPageSize(1);

        return (bool) $collection->getSize();
    }

    /**
     * @param array|null $selected
     * @return array
     */
    protected function getSelectedModels(?array $selected): array
    {
        if($selected) {
            $enabledModels = [];
            foreach(Model::OPTIONS as $modelKey => $modelName) {
                if(in_array($modelName, $selected)) {
                    $enabledModels[] = $modelKey;
                }
            }
        } else {
            $enabledModels = array_keys(Model::OPTIONS);
        }

        return $enabledModels;
    }

    /**
     * @param int|null $scope
     * @return int[]
     */
    protected function getSelectedStoreIds(?int $scope): array
    {
        return $scope ? [$scope] : $this->synchronization->getEnabledStores();
    }
}
