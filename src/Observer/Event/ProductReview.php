<?php

namespace Synerise\Integration\Observer\Event;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\Api\DefaultApi;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\ApiClient\Model\CustomeventRequest;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Api\Context as ContextHelper;
use Synerise\Integration\Helper\Api\Factory\DefaultApiFactory;
use Synerise\Integration\Helper\Api\Identity;
use Synerise\Integration\Helper\Api\Event\Review;
use Synerise\Integration\Observer\AbstractObserver;

class ProductReview  extends AbstractObserver implements ObserverInterface
{
    public const EVENT = 'product_review_save_after';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Api
     */
    protected $apiHelper;

    /**
     * @var DefaultApiFactory
     */
    protected $defaultApiFactory;

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
        Api $apiHelper,
        DefaultApiFactory $defaultApiFactory,
        Identity $identityHelper,
        Review $reviewHelper
    ) {
        $this->storeManager = $storeManager;

        $this->apiHelper = $apiHelper;
        $this->defaultApiFactory = $defaultApiFactory;
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

            $this->sendCustomEvent(
                $this->reviewHelper->prepareProductReviewRequest(
                    self::EVENT,
                    $this->review,
                    $this->storeManager->getStore()->getId(),
                    $this->identityHelper->getClientUuid()
                )
            );

            $this->sendCreateClient(
                $this->reviewHelper->prepareCreateClientRequest(
                    $this->review, $this->identityHelper->getClientUuid()
                )
            );
        } catch (\Exception $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e]);
        }
    }

    /**
     * @param CreateaClientinCRMRequest $createAClientInCrmRequest
     * @param int|null $storeId
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     * @throws ApiException
     * @throws ValidatorException
     */
    public function sendCreateClient(CreateaClientinCRMRequest $createAClientInCrmRequest, int $storeId = null): array
    {
        return $this->getDefaultApiInstance($storeId)
            ->createAClientInCrmWithHttpInfo('4.4', $createAClientInCrmRequest);
    }

    /**
     * @param CustomeventRequest $request
     * @param int|null $storeId
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     * @throws ApiException
     * @throws ValidatorException
     */
    public function sendCustomEvent(CustomeventRequest $request, ?int $storeId = null): array
    {
        return $this->getDefaultApiInstance($storeId)
            ->customEventWithHttpInfo('4.4', $request);
    }

    /**
     * @param int|null $storeId
     * @return DefaultApi
     * @throws ValidatorException
     * @throws ApiException
     */
    public function getDefaultApiInstance(?int $storeId = null): DefaultApi
    {
        return $this->defaultApiFactory->get($this->apiHelper->getApiConfigByScope($storeId));
    }
}