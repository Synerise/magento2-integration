<?php

namespace Synerise\Integration\Helper;

use Magento\Framework\Exception\ValidatorException;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CustomeventRequest;
use Synerise\Integration\Observer\CartAddProduct;
use Synerise\Integration\Observer\CartQtyUpdate;
use Synerise\Integration\Observer\CartRemoveProduct;
use Synerise\Integration\Observer\CartStatus;
use Synerise\Integration\Observer\CatalogProductDeleteBefore;
use Synerise\Integration\Observer\CustomerLogin;
use Synerise\Integration\Observer\CustomerLogout;
use Synerise\Integration\Observer\CustomerRegister;
use Synerise\Integration\Observer\NewsletterSubscriberDeleteAfter;
use Synerise\Integration\Observer\NewsletterSubscriberSaveAfter;
use Synerise\Integration\Observer\OrderPlace;
use Synerise\Integration\Observer\ProductReview;
use Synerise\Integration\Observer\WishlistAddProduct;
use Synerise\Integration\Observer\WishlistRemoveProduct;

class Event
{

    /**
     * @var Api
     */
    private $apiHelper;

    /**
     * @var Tracking
     */
    private $trackingHelper;

    /**
     * @var Catalog
     */
    private $catalogHelper;

    /**
     * @var Customer
     */
    private $customerHelper;

    /**
     * @var Order
     */
    private $orderHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        LoggerInterface $logger,
        Api $apiHelper,
        Tracking $trackingHelper,
        Catalog $catalogHelper,
        Customer $customerHelper,
        Order $orderHelper
    ) {
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
        $this->trackingHelper = $trackingHelper;
        $this->catalogHelper = $catalogHelper;
        $this->customerHelper = $customerHelper;
        $this->orderHelper = $orderHelper;
    }

    /**
     * @throws ApiException
     * @throws ValidatorException
     */
    public function sendEvent($event_name, $payload, int $storeId, int $entityId = null, int $timeout = null)
    {
        try {
            $apiInstance = $this->apiHelper->getDefaultApiInstance(
                $storeId,
                $timeout ?: $this->apiHelper->getScheduledRequestTimeout($storeId)
            );

            switch ($event_name) {
                case CartAddProduct::EVENT:
                    $apiInstance->clientAddedProductToCart('4.4', $payload);
                    break;
                case CartRemoveProduct::EVENT:
                    $apiInstance->clientRemovedProductFromCart('4.4', $payload);
                    break;
                case CartQtyUpdate::EVENT:
                case CartStatus::EVENT:
                case ProductReview::EVENT:
                case WishlistRemoveProduct::EVENT:
                $apiInstance->customEvent('4.4', $payload);
                    break;
                case CustomerRegister::EVENT:
                    $apiInstance->clientRegistered('4.4', $payload);
                    break;
                case CustomerLogin::EVENT:
                    $apiInstance->clientLoggedIn('4.4', $payload);
                    break;
                case CustomerLogout::EVENT:
                    $apiInstance->clientLoggedOut('4.4', $payload);
                    break;
                case OrderPlace::EVENT:
                    $apiInstance->createATransaction('4.4', $payload);
                    if ($entityId) {
                        $this->orderHelper->markItemsAsSent([$entityId]);
                    }
                    break;
                case CatalogProductDeleteBefore::EVENT:
                    $this->catalogHelper->sendItemsToSyneriseWithCatalogCheck($payload, $storeId);
                    break;
                case WishlistAddProduct::EVENT:
                    $apiInstance->clientAddedProductToFavorites('4.4', $payload);
                    break;
                case NewsletterSubscriberDeleteAfter::EVENT:
                case NewsletterSubscriberSaveAfter::EVENT:
                case 'ADD_OR_UPDATE_CLIENT':
                    list($body, $statusCode, $headers) = $apiInstance->batchAddOrUpdateClientsWithHttpInfo('application/json', '4.4', [ $payload ]);
                    if ($statusCode != 202) {
                        $this->logger->error('Client update failed', ['api_response_body' => $body]);
                    } elseif ($entityId) {
                        $this->customerHelper->markCustomersAsSent([$entityId], $storeId);
                    }
            }
        } catch (\Synerise\ApiClient\ApiException $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e, 'api_response_body' => $e->getResponseBody()]);
            throw $e;
        }
    }

    public function prepareCartStatusEvent(\Magento\Quote\Model\Quote $quote, $totalAmount, $totalQuantity): CustomeventRequest
    {
        $params = [
            'source' => $this->trackingHelper->getSource(),
            'applicationName' => $this->trackingHelper->getApplicationName(),
            'storeId' => $this->trackingHelper->getStoreId(),
            'storeUrl' => $this->trackingHelper->getStoreBaseUrl(),
            'products' => $this->catalogHelper->prepareProductsFromQuote($quote),
            'totalAmount' => $totalAmount,
            'totalQuantity' => $totalQuantity
        ];

        if($this->trackingHelper->shouldIncludeParams($this->trackingHelper->getStoreId()) && $this->trackingHelper->getCookieParams()) {
            $params['snrs_params'] = $this->trackingHelper->getCookieParams();
        }

        return new CustomeventRequest([
            'time' => $this->trackingHelper->getCurrentTime(),
            'action' => 'cart.status',
            'label' => 'CartStatus',
            'client' => $this->trackingHelper->prepareClientDataFromQuote($quote),
            'params' => $params
        ]);
    }
}
