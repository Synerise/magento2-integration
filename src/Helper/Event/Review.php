<?php

namespace Synerise\Integration\Helper\Event;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Review\Model\Rating\Option\VoteFactory;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\ApiClient\Model\CustomeventRequest;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Api\DefaultApiFactory;
use Synerise\Integration\Helper\Data\Context as ContextHelper;
use Synerise\Integration\Helper\Data\Product;

class Review extends AbstractEvent
{
    /**
     * @var ProductRepositoryInterface
     */

    private $productRepository;
    /**
     * @var VoteFactory
     */
    private $voteFactory;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        VoteFactory $voteFactory,
        Api $apiHelper,
        ContextHelper $contextHelper,
        DefaultApiFactory $defaultApiFactory
    ) {
        $this->productRepository = $productRepository;
        $this->voteFactory = $voteFactory;

        parent::__construct($apiHelper, $contextHelper, $defaultApiFactory);
    }

    public function prepareProductReviewRequest($event, \Magento\Review\Model\Review $review, $storeId, $uuid): CustomeventRequest {
        return new CustomeventRequest(
            $this->prepareEventData(
                $this->getEventLabel($event),
                new \Synerise\ApiClient\Model\Client([
                    'uuid' => $uuid,
                    'custom_id' => $review->getCustomerId()
                ]),
                $this->prepareParams($review, $storeId),
                'product.addReview'
            )
        );
    }

    public function prepareParams(\Magento\Review\Model\Review $review, $storeId)
    {
        $params = [
            'nickname' => $review->getNickname(),
            'title' => $review->getTitle(),
            'detail' => $review->getDetail(),
        ];

        $product = $this->productRepository->getById($review->getEntityPkValue());

        if ($product) {
            $params['sku'] =  $product->getSku();
        }

        $votesCollection = $this->voteFactory->create()->getResourceCollection()
            ->setReviewFilter($review->getReviewId())
            ->setStoreFilter($storeId)
            ->addRatingInfo($storeId)
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

        return $params;
    }

    /**
     * @param $review
     * @param $uuid
     * @return CreateaClientinCRMRequest
     */
    public function prepareCreateClientRequest($review, $uuid = null)
    {
        return new CreateaClientinCRMRequest([
            'uuid' => $uuid,
            'display_name' => $review->getNickname()
        ]);
    }
}