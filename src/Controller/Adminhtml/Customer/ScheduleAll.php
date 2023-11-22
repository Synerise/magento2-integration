<?php

namespace Synerise\Integration\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Synerise\Integration\Helper\Synchronization;
use \Synerise\Integration\Model\Synchronization\MessageQueue\Data\Scheduler\Publisher;
use Synerise\Integration\Model\Synchronization\Provider\Customer as Provider;
use Synerise\Integration\Model\Synchronization\Sender\Customer as Sender;

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
        $storeIds = [];
        foreach ($this->synchronization->getEnabledStores() as $storeId)
        {
            /** @var Collection $collection */
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

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('synerise/dashboard/index');
    }
}
