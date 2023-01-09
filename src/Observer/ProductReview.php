<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Identity;
use Synerise\Integration\Helper\Event\Review;

class ProductReview  extends AbstractObserver implements ObserverInterface
{
    public const EVENT = 'product_review_save_after';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Identity
     */
    protected $identityHelper;

    /**
     * @var Review
     */
    protected $reviewHelper;

    /**
     * @var \Magento\Review\Model\Review
     */
    private $review;


    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        Review $reviewHelper,
        Identity $identityHelper
    ) {
        $this->storeManager = $storeManager;

        $this->identityHelper = $identityHelper;
        $this->reviewHelper = $reviewHelper;

        parent::__construct($scopeConfig, $logger);
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->isLiveEventTrackingEnabled(self::EVENT)) {
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

            $this->reviewHelper->sendCustomEvent(
                $this->reviewHelper->prepareProductReviewRequest(
                    self::EVENT,
                    $this->review,
                    $this->storeManager->getStore()->getId(),
                    $this->identityHelper->getClientUuid()
                )
            );

            $this->identityHelper->sendCreateClient(
                $this->reviewHelper->prepareCreateClientRequest(
                    $this->review, $this->identityHelper->getClientUuid()
                )
            );
        } catch (\Exception $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e]);
        }
    }
}