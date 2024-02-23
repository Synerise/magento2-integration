<?php

namespace Synerise\Integration\SyneriseApi\Mapper;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Review\Model\ResourceModel\Rating\Option\Vote\CollectionFactory as VoteCollectionFactory;
use Magento\Review\Model\Review;
use Synerise\ApiClient\Model\CustomeventRequest;
use Synerise\Integration\Helper\Tracking\Context;

class ReviewProduct
{

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var VoteCollectionFactory
     */
    protected $voteCollectionFactory;

    /**
     * @var Context
     */
    protected $contextHelper;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param VoteCollectionFactory $voteCollectionFactory
     * @param Context $contextHelper
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        VoteCollectionFactory $voteCollectionFactory,
        Context $contextHelper
    ) {
        $this->productRepository = $productRepository;
        $this->voteCollectionFactory = $voteCollectionFactory;
        $this->contextHelper = $contextHelper;
    }

    /**
     * Prepare custom product review event request
     *
     * @param string $event
     * @param Review $review
     * @param int $storeId
     * @param string|null $uuid
     * @return CustomeventRequest
     * @throws InvalidArgumentException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws NotFoundException
     */
    public function prepareRequest(
        string $event,
        Review $review,
        int $storeId,
        ?string $uuid = null
    ): CustomeventRequest
    {
        $product = $this->productRepository->getById($review->getEntityPkValue());
        if (!$product) {
            throw new NotFoundException(__('Product with ID "%1" not found.', $review->getEntityPkValue()));
        }

        $client = [];

        if ($uuid) {
            $client['uuid'] = $uuid;
        }

        $customerId = $review->getCustomerId();
        if ($customerId) {
            $client['custom_id'] = $customerId;
        }

        if (empty($client)) {
            throw new InvalidArgumentException(__('Client identity could not be determined.'));
        }
        
        $params = $this->contextHelper->prepareContextParams();
        $params['sku'] = $product->getSku();
        $params['nickname'] = $review->getNickname();
        $params['title'] = $review->getTitle();
        $params['detail'] = $review->getDetail();

        $ratingVotes = $this->voteCollectionFactory->create()
            ->setReviewFilter($review->getReviewId())
            ->addRatingInfo($storeId);

        if (count($ratingVotes)) {
            $params['ratings'] = [];
            foreach ($ratingVotes as $vote) {
                $params['ratings'][] = [
                    'rating_code' => $vote->getRatingCode(),
                    'percent' => $vote->getPercent(),
                    'value' => $vote->getValue()
                ];
            }
        }

        return new CustomeventRequest([
            'event_salt' => $this->contextHelper->generateEventSalt(),
            'time' => $this->contextHelper->getCurrentTime(),
            'label' => $this->contextHelper->getEventLabel($event),
            'action' => 'product.addReview',
            'client' => $client,
            'params' => $params
        ]);
    }
}