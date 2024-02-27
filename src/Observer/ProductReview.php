<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Review\Model\Review;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\SyneriseApi\Mapper\CustomerAdd;
use Synerise\Integration\SyneriseApi\Mapper\ReviewProduct;
use Synerise\Integration\SyneriseApi\Sender\Event;
use Synerise\Integration\SyneriseApi\Sender\Data\Customer as CustomerSender;
use Synerise\Integration\MessageQueue\Publisher\Event as Publisher;
use Synerise\Integration\Helper\Tracking;

class ProductReview implements ObserverInterface
{
    public const EVENT = 'product_review_save_after';

    public const CUSTOMER_UPDATE = 'customer_update_product_review';

    /**
     * @var Review|null
     */
    protected $review = null;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var ReviewProduct
     */
    protected $reviewProduct;

    /**
     * @var CustomerAdd
     */
    private $customerAdd;

    /**
     * @var Publisher
     */
    protected $publisher;

    /**
     * @var Event
     */
    protected $sender;

    /**
     * @var CustomerSender
     */
    private $customerSender;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Logger $loggerHelper
     * @param Tracking $trackingHelper
     * @param ReviewProduct $reviewProduct
     * @param CustomerAdd $customerAdd
     * @param Publisher $publisher
     * @param Event $sender
     * @param CustomerSender $customerSender
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Logger $loggerHelper,
        Tracking $trackingHelper,
        ReviewProduct $reviewProduct,
        CustomerAdd $customerAdd,
        Publisher $publisher,
        Event $sender,
        CustomerSender $customerSender
    ) {
        $this->storeManager = $storeManager;
        $this->loggerHelper = $loggerHelper;
        $this->trackingHelper = $trackingHelper;
        $this->reviewProduct = $reviewProduct;
        $this->customerAdd = $customerAdd;
        $this->publisher = $publisher;
        $this->sender = $sender;
        $this->customerSender = $customerSender;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            if (!$this->trackingHelper->getContext()->isFrontend()) {
                return;
            }

            $storeId = $this->storeManager->getStore()->getId();
            if (!$this->trackingHelper->isEventTrackingAvailable(self::EVENT, $storeId)) {
                return;
            }

            if ($observer->getObject()) {
                $this->review = $observer->getObject();
                return;
            }

            if (!$this->review || $this->review->getEntityId() != 1) {
                return;
            }

            $uuid = $this->trackingHelper->getClientUuid();

            $customEventRequest = $this->reviewProduct->prepareRequest(
                self::EVENT,
                $this->review,
                $storeId,
                $uuid
            );

            $guestCustomerRequest = $uuid ? $this->customerAdd->prepareRequestFromReview($this->review, $uuid) : null;

            if ($this->trackingHelper->isEventMessageQueueAvailable(self::EVENT, $storeId)) {
                $this->publisher->publish(self::EVENT, $customEventRequest, $storeId);
                if ($guestCustomerRequest) {
                    $this->publisher->publish(self::CUSTOMER_UPDATE, $guestCustomerRequest, $storeId);
                }
            } else {
                $this->sender->send(self::EVENT, $customEventRequest, $storeId);
                if ($guestCustomerRequest) {
                    $this->customerSender->batchAddOrUpdateClients([$guestCustomerRequest], $storeId);
                }
            }
        } catch (NotFoundException|InvalidArgumentException $e) {
            return;
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->loggerHelper->error($e);
            }
        }
    }
}
