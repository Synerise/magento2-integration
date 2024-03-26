<?php

namespace Synerise\Integration\Observer\Event;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Review\Model\Review;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\Helper\Tracking\State;
use Synerise\Integration\Model\Tracking\ConfigFactory;
use Synerise\Integration\SyneriseApi\Mapper\Data\CustomerFromReview;
use Synerise\Integration\SyneriseApi\Mapper\Event\ReviewAdd;
use Synerise\Integration\SyneriseApi\Sender\Event;
use Synerise\Integration\SyneriseApi\Sender\Data\Customer as CustomerSender;
use Synerise\Integration\MessageQueue\Publisher\Event as Publisher;

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
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var Cookie
     */
    protected $cookieHelper;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var State
     */
    protected $stateHelper;

    /**
     * @var ReviewAdd
     */
    protected $reviewAdd;

    /**
     * @var CustomerFromReview
     */
    private $customerFromReview;

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
     * @param ConfigFactory $configFactory
     * @param Cookie $cookieHelper
     * @param Logger $loggerHelper
     * @param State $stateHelper
     * @param ReviewAdd $reviewAdd
     * @param CustomerFromReview $customerFromReview
     * @param Publisher $publisher
     * @param Event $sender
     * @param CustomerSender $customerSender
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ConfigFactory $configFactory,
        Cookie $cookieHelper,
        Logger $loggerHelper,
        State $stateHelper,
        ReviewAdd $reviewAdd,
        CustomerFromReview $customerFromReview,
        Publisher $publisher,
        Event $sender,
        CustomerSender $customerSender
    ) {
        $this->storeManager = $storeManager;
        $this->configFactory = $configFactory;
        $this->cookieHelper = $cookieHelper;
        $this->loggerHelper = $loggerHelper;
        $this->stateHelper = $stateHelper;
        $this->reviewAdd = $reviewAdd;
        $this->customerFromReview = $customerFromReview;
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
            if (!$this->stateHelper->isFrontend()) {
                return;
            }

            $storeId = $this->storeManager->getStore()->getId();
            $config = $this->configFactory->create($storeId);
            if (!$config->isEventTrackingEnabled(self::EVENT)) {
                return;
            }

            if ($observer->getObject()) {
                $this->review = $observer->getObject();
                return;
            }

            if (!$this->review || $this->review->getEntityId() != 1) {
                return;
            }

            $uuid = $this->cookieHelper->getSnrsUuid();
            $customEventRequest = $this->reviewAdd->prepareRequest(
                self::EVENT,
                $this->review,
                $storeId,
                $uuid
            );

            $guestCustomerRequest = $uuid ? $this->customerFromReview->prepareRequest($this->review, $uuid) : null;

            if ($config->isEventMessageQueueEnabled(self::EVENT)) {
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
