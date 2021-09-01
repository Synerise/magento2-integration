<?php

namespace Synerise\Integration\Controller\Adminhtml\Catalog;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Cron\Synchronization;

class MassUpdate extends Action
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Synchronization
     */
    protected $synchronization;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        LoggerInterface $logger,
        Synchronization $synchronization
    ) {
        $this->logger = $logger;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->synchronization = $synchronization;

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
        $collection = $this->filter->getCollection($this->collectionFactory->create());

        try {
            foreach($collection as $product) {
                $this->synchronization->addItemToQueueByWebsiteIds(
                    'product',
                    $product->getWebsiteIds(),
                    $product->getId()
                );
            }

            $this->messageManager->addSuccess(__('A total of %1 record(s) have been added to synchronization queue.', $collection->getSize()));
        } catch (\Exception $e) {
            $this->logger->error('Failed to add records to synchronization queue', ['exception' => $e]);
            $this->messageManager->addError(__('Failed to add records to synchronization queue'));
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('catalog/product/index');
    }

}