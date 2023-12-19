<?php

namespace Synerise\Integration\Controller\Adminhtml\Customer;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\MessageQueue\Filter;
use Synerise\Integration\MessageQueue\Publisher\Data\Scheduler as Publisher;
use Synerise\Integration\SyneriseApi\Sender\Data\Customer as Sender;

class ScheduleAll extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Synerise_Integration::synchronization_catalog';

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var Publisher
     */
    private $publisher;

    /**
     * @var Synchronization
     */
    private $synchronization;

    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        Filter $filter,
        Publisher $publisher,
        Synchronization $synchronization
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->filter = $filter;
        $this->publisher = $publisher;
        $this->synchronization = $synchronization;
        $this->messageManager = $context->getMessageManager();

        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return Redirect
     * @throws LocalizedException | Exception
     */
    public function execute()
    {
        if (!$this->synchronization->isSynchronizationEnabled()) {
            $this->messageManager->addErrorMessage(
                __('Synchronization is disabled. Please review your configuration.')
            );
        } elseif (!$this->synchronization->isEnabledModel(Sender::MODEL)) {
            $this->messageManager->addErrorMessage(
                __('%1s are excluded from synchronization.', ucfirst(Sender::MODEL))
            );
        } else {
            $storeIds = [];
            foreach ($this->synchronization->getEnabledStores() as $storeId)
            {
                /** @var Collection $collection */
                $collection = $this->filter->addStoreFilter(
                    $this->collectionFactory->create(),
                    $storeId
                );

                if ($collection->getSize()) {
                    $storeIds[] = $storeId;
                }
            }

            if (!empty($storeIds)) {
                $this->publisher->schedule(
                    Sender::MODEL,
                    $storeIds
                );
                $this->messageManager->addSuccessMessage(
                    __(
                        '%1 synchronization has been scheduled for stores: %2',
                        ucfirst(Sender::MODEL),
                        implode(',',$storeIds)
                    )
                );
            } else {
                $this->messageManager->addErrorMessage(
                    __(
                        'Nothing to synchronize. No stores enabled for %1s.',
                        Sender::MODEL
                    )
                );
            }
        }
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('synerise/dashboard/index');
    }
}
