<?php

namespace Synerise\Integration\Observer\Event;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\ApiClient\Model\CustomeventRequest;
use Synerise\Integration\Helper\Api\Identity;
use Synerise\Integration\Helper\Api\Event\Review;
use Synerise\Integration\Helper\Event;
use Synerise\Integration\Helper\MessageQueue;
use Synerise\Integration\Observer\AbstractObserver;

class ProductReview  extends AbstractObserver implements ObserverInterface
{
    public const EVENT = 'product_review_save_after';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Event
     */
    protected $eventsHelper;

    /**
     * @var MessageQueue
     */
    protected $queueHelper;

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
        ScopeConfigInterface  $scopeConfig,
        LoggerInterface       $logger,
        StoreManagerInterface $storeManager,
        Event                 $eventsHelper,
        MessageQueue          $queueHelper,
        Identity              $identityHelper,
        Review                $reviewHelper
    ) {
        $this->storeManager = $storeManager;

        $this->eventsHelper = $eventsHelper;
        $this->queueHelper = $queueHelper;
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

            $storeId = $this->storeManager->getStore()->getId();

            $eventRequest = $this->reviewHelper->prepareProductReviewRequest(
                self::EVENT,
                $this->review,
                $storeId,
                $this->identityHelper->getClientUuid()
            );

            $this->publishOrSendEvent(static::EVENT, $eventRequest, $storeId);

            $updateRequest = $this->reviewHelper->prepareCreateClientRequest(
                $this->review, $this->identityHelper->getClientUuid()
            );

            $this->publishOrSendClientUpdate($updateRequest, $storeId);

        } catch (\Exception $e) {
            $this->logger->error('Synerise Error', ['exception' => $e]);
        }
    }

    /**
     * @param string $eventName
     * @param CustomeventRequest $request
     * @param int $storeId
     * @return void
     * @throws ValidatorException
     */
    public function publishOrSendEvent(string $eventName, CustomeventRequest $request, int $storeId): void
    {
        try {
            if ($this->queueHelper->isQueueAvailable()) {
                $this->queueHelper->publishEvent($eventName, $request, $storeId);
            } else {
                $this->eventsHelper->sendEvent($eventName, $request, $storeId);
            }
        } catch (ApiException $e) {
        }
    }

    /**
     * @param CreateaClientinCRMRequest $request
     * @param int $storeId
     * @return void
     * @throws ValidatorException
     */
    public function publishOrSendClientUpdate(CreateaClientinCRMRequest $request, int $storeId): void
    {
        try {
            if ($this->queueHelper->isQueueAvailable()) {
                $this->queueHelper->publishEvent(Event::ADD_OR_UPDATE_CLIENT, $request, $storeId);
            } else {
                $this->eventsHelper->sendEvent(Event::ADD_OR_UPDATE_CLIENT, $request, $storeId);
            }
        } catch (ApiException $e) {
        }
    }
}