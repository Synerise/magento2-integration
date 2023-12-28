<?php

namespace Synerise\Integration\Observer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Review\Model\Rating\Option\VoteFactory;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\ApiClient\Model\CustomeventRequest;
use Synerise\Integration\Helper\DataStorage;
use Synerise\Integration\SyneriseApi\Sender\Event;
use Synerise\Integration\SyneriseApi\Sender\Data\Customer as CustomerSender;
use Synerise\Integration\MessageQueue\Publisher\Event as Publisher;
use Synerise\Integration\Helper\Tracking;

class ProductReview implements ObserverInterface
{
    const EVENT = 'product_review_save_after';

    const CUSTOMER_UPDATE = 'customer_update_product_review';

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var VoteFactory
     */
    protected $voteFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var DataStorage
     */
    protected $data;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var \Magento\Review\Model\Review
     */
    protected $review;

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

    public function __construct(
        ProductRepositoryInterface $productRepository,
        VoteFactory $voteFactory,
        StoreManagerInterface $storeManager,
        Tracking $trackingHelper,
        Publisher $publisher,
        Event $sender,
        CustomerSender $customerSender
    ) {
        $this->productRepository = $productRepository;
        $this->voteFactory = $voteFactory;
        $this->storeManager = $storeManager;
        $this->trackingHelper = $trackingHelper;
        $this->publisher = $publisher;
        $this->sender = $sender;
        $this->customerSender = $customerSender;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->trackingHelper->isLiveEventTrackingEnabled(self::EVENT)) {
            return;
        }

        try {
            if ($observer->getObject()) {
                $this->review = $observer->getObject();
                return;
            }

            if (!$this->review || $this->review->getEntityId() != 1) {
                return;
            }

            $product = $this->productRepository->getById($this->review->getEntityPkValue());
            if (!$product) {
                return;
            }

            $client = ['uuid' => $this->trackingHelper->getClientUuid()];

            $customerId = $this->review->getCustomerId();
            $storeId = $this->storeManager->getStore()->getId();

            if ($customerId) {
                $client['custom_id'] = $customerId;
            }

            $params = [
                'sku' => $product->getSku(),
                'nickname' => $this->review->getNickname(),
                'title' => $this->review->getTitle(),
                'detail' => $this->review->getDetail(),
                'source' => $this->trackingHelper->getSource(),
                'applicationName' => $this->trackingHelper->getApplicationName()
            ];

            $votesCollection = $this->voteFactory->create()->getResourceCollection()
                ->setReviewFilter($this->review->getReviewId())
                ->setStoreFilter($this->storeManager->getStore()->getId())
                ->addRatingInfo($this->storeManager->getStore()->getId())
                ->load();

            if (count($votesCollection)) {
                $params['ratings'] = [];
                foreach ($votesCollection as $vote) {
                    $params['ratings'][] = [
                        'rating_code' => $vote->getRatingCode(),
                        'percent' => $vote->getPercent(),
                        'value' => $vote->getValue()
                    ];
                }
            }

            $customEventRequest = new CustomeventRequest([
                'event_salt' => $this->trackingHelper->generateEventSalt(),
                'time' => $this->trackingHelper->getCurrentTime(),
                'action' => 'product.addReview',
                'label' => $this->trackingHelper->getEventLabel(self::EVENT),
                'client' => $client,
                'params' => $params
            ]);

            $guestCustomerRequest = new CreateaClientinCRMRequest([
                'uuid' => $this->trackingHelper->getClientUuid(),
                'display_name' => $this->review->getNickname()
            ]);

            if ($this->trackingHelper->isQueueAvailable(self::EVENT, $storeId)) {
                $this->publisher->publish(self::EVENT, $customEventRequest, $storeId);
                $this->publisher->publish(self::CUSTOMER_UPDATE, $guestCustomerRequest, $storeId);
            } else {
                $this->sender->send(self::EVENT, $customEventRequest, $storeId);
                $this->customerSender->batchAddOrUpdateClients($guestCustomerRequest, $storeId);
            }
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->trackingHelper->getLogger()->error($e);
            }
        }
    }
}
