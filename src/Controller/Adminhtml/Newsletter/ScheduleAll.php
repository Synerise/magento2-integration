<?php

namespace Synerise\Integration\Controller\Adminhtml\Newsletter;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Synerise\Integration\Helper\Synchronization;
use \Synerise\Integration\Model\Synchronization\MessageQueue\Data\Scheduler\Publisher;
use Synerise\Integration\Model\Synchronization\Provider\Subscriber as Provider;
use Synerise\Integration\Model\Synchronization\Sender\Subscriber as Sender;

class ScheduleAll extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Synerise_Integration::synchronization_catalog';


    /**
     * @var Provider
     */
    protected $provider;

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
        Provider $provider,
        Publisher $publisher,
        Synchronization $synchronization
    ) {
        $this->provider = $provider;
        $this->publisher = $publisher;
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
        if ($this->synchronization->isEnabledModel(Sender::MODEL)) {


            $storeIds = [];
            foreach ($this->synchronization->getEnabledStores() as $storeId)
            {
                $collection = $this->provider->createCollection()
                    ->addStoreFilter($storeId)
                    ->getCollection();

                if ($collection->getSize()) {
                    $storeIds[] = $storeId;
                }
            }

            if (!empty($storeIds)) {
                $this->publisher->schedule(
                    Sender::MODEL,
                    $storeIds
                );
            }
        } else {
            $this->messageManager->addErrorMessage(
                __('Nothing to synchronize. %1s are excluded from synchronization.', Sender::MODEL)
            );
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('synerise/dashboard/index');
    }
}
