<?php

namespace Synerise\Integration\Observer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Review\Model\Rating\Option\VoteFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\ApiClient\Model\CustomeventRequest;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Customer;
use Synerise\Integration\Helper\DataStorage;
use Synerise\Integration\Helper\Tracking;

class ProductReview implements ObserverInterface
{
    public const EVENT = 'product_review_save_after';

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var VoteFactory
     */
    protected  $voteFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var DataStorage
     */
    protected $data;

    /**
     * @var Api
     */
    protected $apiHelper;

    /**
     * @var Customer
     */
    protected $customerHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var \Magento\Review\Model\Review
     */
    private $review;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        VoteFactory $voteFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        Api $apiHelper,
        Customer $customerHelper,
        Tracking $trackingHelper
    ) {
        $this->productRepository = $productRepository;
        $this->voteFactory = $voteFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
        $this->customerHelper = $customerHelper;
        $this->trackingHelper = $trackingHelper;
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

            $client = [
                'uuid' => $this->trackingHelper->getClientUuid()
            ];
            $customerId = $this->review->getCustomerId();
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
                'time' => $this->trackingHelper->getCurrentTime(),
                'action' => 'product.addReview',
                'label' => $this->trackingHelper->getEventLabel(self::EVENT),
                'client' => $client,
                'params' => $params
            ]);

            $this->apiHelper->getDefaultApiInstance()
                ->customEvent('4.4', $customEventRequest);

            $createAClientInCrmRequests = [
                new CreateaClientinCRMRequest([
                    'uuid' => $this->trackingHelper->getClientUuid(),
                    'display_name' => $this->review->getNickname()
                ])
            ];

            $this->customerHelper->sendCustomersToSynerise(
                $createAClientInCrmRequests,
                $this->storeManager->getStore()->getId()
            );
        } catch (\Exception $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e]);
        }
    }
}
