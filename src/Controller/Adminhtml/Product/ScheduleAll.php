<?php

namespace Synerise\Integration\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Model\Synchronization\Filter;
use Synerise\Integration\Model\Synchronization\MessageQueue\Data\Scheduler\Publisher;
use Synerise\Integration\Model\Synchronization\Sender\Product as Sender;

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
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException | \Exception
     */
    public function execute()
    {
        if ($this->synchronization->isEnabledModel(Sender::MODEL)) {
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
                        '%1 synchronization has been scheduled for stores: %2', ucfirst(Sender::MODEL),
                        Sender::MODEL,
                        implode(',',$storeIds)
                    )
                );
            } else {
                $this->messageManager->addErrorMessage(
                    __(
                        '%1 synchronization has been scheduled for stores: %2',
                        ucfirst(Sender::MODEL),
                        implode(',',$storeIds)
                    )
                );
            }
        } else {
            $this->messageManager->addErrorMessage(
                __('%1s are excluded from synchronization.', ucfirst(Sender::MODEL), Sender::MODEL)
            );
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('synerise/dashboard/index');
    }
}
