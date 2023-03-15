<?php

namespace Synerise\Integration\Helper;

use Magento\Framework\Exception\ValidatorException;
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

class Event extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Synerise\Integration\Helper\Api
     */
    private $apiHelper;

    /**
     * @var \Synerise\Integration\Helper\Tracking
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

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Synerise\Integration\Helper\Api $apiHelper,
        \Synerise\Integration\Helper\Tracking $trackingHelper,
        \Synerise\Integration\Helper\Catalog $catalogHelper,
        \Synerise\Integration\Helper\Customer $customerHelper,
        \Synerise\Integration\Helper\Order $orderHelper
    ) {
        parent::__construct($context);
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
                case ProductReview::EVENT:
                case CartStatus::EVENT:
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
                        $this->_logger->error('Client update failed');
                    } elseif ($entityId) {
                        $this->customerHelper->markCustomersAsSent([$entityId], $storeId);
                    }
            }
        } catch (\Synerise\ApiClient\ApiException $e) {
            $this->_logger->error('Synerise Api request failed', ['exception' => $e, 'api_response_body' => $e->getResponseBody()]);
            throw $e;
        }
    }

    public function prepareCartStatusEvent(\Magento\Quote\Model\Quote $quote, $totalAmount, $totalQuantity): CustomeventRequest
    {
        return new CustomeventRequest([
            'time' => $this->trackingHelper->getCurrentTime(),
            'action' => 'cart.status',
            'label' => 'CartStatus',
            'client' => $this->trackingHelper->prepareClientDataFromQuote($quote),
            'params' => [
                'source' => $this->trackingHelper->getSource(),
                'applicationName' => $this->trackingHelper->getApplicationName(),
                'storeId' => $this->trackingHelper->getStoreId(),
                'storeUrl' => $this->trackingHelper->getStoreBaseUrl(),
                'products' => $this->catalogHelper->prepareProductsFromQuote($quote),
                'totalAmount' => $totalAmount,
                'totalQuantity' => $totalQuantity
            ]
        ]);
    }
}
