<?php

namespace Synerise\Integration\Controller\Adminhtml\Synchronization\Batch;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use PhpAmqpLib\Exception\AMQPExceptionInterface;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\MessageQueue\Publisher\Data\Batch as Publisher;
use Synerise\Integration\Model\Synchronization\Config;
use Synerise\Integration\SyneriseApi\Sender\Data\Order as Sender;

class Order extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'Synerise_Integration::synchronization_order';

    /**
     * @var Logger
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
     * @var Publisher
     */
    private $publisher;

    /**
     * @var Config
     */
    private $synchronization;

    /**
     * @param Context $context
     * @param Logger $logger
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param Publisher $publisher
     * @param Config $synchronization
     */
    public function __construct(
        Context $context,
        Logger $logger,
        Filter $filter,
        CollectionFactory $collectionFactory,
        Publisher $publisher,
        Config $synchronization
    ) {
        $this->logger = $logger;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->publisher = $publisher;
        $this->synchronization = $synchronization;

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
        if (!$this->synchronization->isEnabled()) {
            $this->messageManager->addErrorMessage(
                __('Synchronization is disabled. Please review your configuration.')
            );
        } elseif (!$this->synchronization->isModelEnabled(Sender::MODEL)) {
            $this->messageManager->addErrorMessage(
                __('%1s are excluded from synchronization.', ucfirst(Sender::MODEL))
            );
        } else {
            $storeIds = [];
            $itemsCount = 0;
            $enabledStoreIds = $this->synchronization->getConfiguredStores();
            /** @var Collection $collection */
            $collection = $this->filter->getCollection($this->collectionFactory->create());

            try {
                foreach ($enabledStoreIds as $enabledStoreId) {
                    $collection->addFieldToFilter('store_id', ['eq' => $enabledStoreId]);
                    $ids = $collection->getAllIds();
                    if (!empty($ids)) {
                        $this->publisher->schedule(
                            Sender::MODEL,
                            $collection->getAllIds(),
                            $enabledStoreId
                        );
                        $storeIds[] = $enabledStoreId;
                        $itemsCount += $collection->getSize();
                    }
                }

                if (!empty($storeIds)) {
                    $this->messageManager->addSuccessMessage(
                        __(
                            'A total of %1 %2(s) have been added to synchronization queue for stores: %3',
                            $itemsCount,
                            Sender::MODEL,
                            implode(',', $storeIds)
                        )
                    );
                } else {
                    $this->messageManager->addErrorMessage(
                        __(
                            'Nothing to synchronize. No stores enabled for selected %1(s).',
                            Sender::MODEL
                        )
                    );
                }
            } catch (\LogicException|AMQPExceptionInterface $e) {
                $this->logger->debug('Failed to add orders to synchronization queue', ['exception' => $e]);
                $this->messageManager->addErrorMessage(__('Failed to add orders to synchronization queue. Please review your amqp config.'));
            } catch (Exception $e) {
                $this->logger->error('Failed to add orders to synchronization queue', ['exception' => $e]);
                $this->messageManager->addErrorMessage(__('Failed to add orders to synchronization queue'));
            }
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('sales/order/index');
    }
}
