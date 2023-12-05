<?php

namespace Synerise\Integration\MessageQueue\Sender;

use Magento\Framework\Exception\ValidatorException;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\MessageQueue\Sender\Data\Customer as CustomerSender;
use Synerise\Integration\MessageQueue\Sender\Data\Product as ProductSender;
use Synerise\Integration\MessageQueue\Sender\Data\Subscriber as SubscriberSender;
use Synerise\Integration\Observer\CartAddProduct;
use Synerise\Integration\Observer\CartQtyUpdate;
use Synerise\Integration\Observer\CartRemoveProduct;
use Synerise\Integration\Observer\CartStatus;
use Synerise\Integration\Observer\CatalogProductDeleteBefore;
use Synerise\Integration\Observer\CustomerLogin;
use Synerise\Integration\Observer\CustomerLogout;
use Synerise\Integration\Observer\CustomerRegister;
use Synerise\Integration\Observer\NewsletterSubscriberDeleteAfter;
use Synerise\Integration\Observer\OrderPlace;
use Synerise\Integration\Observer\ProductReview;
use Synerise\Integration\Observer\WishlistAddProduct;
use Synerise\Integration\Observer\WishlistRemoveProduct;

class Event
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Api
     */
    private $apiHelper;

    /**
     * @var CustomerSender
     */
    private $customerSender;

    /**
     * @var ProductSender
     */
    private $productSender;

    /**
     * @var SubscriberSender
     */
    private $subscriberSender;

    public function __construct(
        LoggerInterface $logger,
        Api $apiHelper,
        CustomerSender $customerSender,
        ProductSender $productSender,
        SubscriberSender $subscriberSender
    ) {
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
        $this->customerSender = $customerSender;
        $this->productSender = $productSender;
        $this->subscriberSender = $subscriberSender;
    }

    /**
     * @param $event_name
     * @param $payload
     * @param int $storeId
     * @param int|null $timeout
     * @return void
     * @throws ApiException
     * @throws ValidatorException
     * @throws \Synerise\CatalogsApiClient\ApiException
     */
    public function send($event_name, $payload, int $storeId, int $timeout = null)
    {
        try {
            $timeout = $timeout ?: $this->apiHelper->getScheduledRequestTimeout($storeId);
            $apiInstance = $this->apiHelper->getDefaultApiInstance(
                $storeId,
                $timeout
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
                case WishlistAddProduct::EVENT:
                    $apiInstance->clientAddedProductToFavorites('4.4', $payload);
                    break;
                case CatalogProductDeleteBefore::EVENT:
                    $this->productSender->addItemsBatchWithCatalogCheck($payload, $storeId);
                    break;
                case NewsletterSubscriberDeleteAfter::EVENT:
                    $this->subscriberSender->batchAddOrUpdateClients([$payload], $storeId, $timeout);
                    break;
                case OrderPlace::CUSTOMER_UPDATE:
                case ProductReview::CUSTOMER_UPDATE:
                    $this->customerSender->batchAddOrUpdateClients([$payload], $storeId, $timeout);
            }
        } catch (ApiException $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e, 'api_response_body' => $e->getResponseBody()]);
            throw $e;
        }
    }
}
