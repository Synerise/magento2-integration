<?php

namespace Synerise\Integration\MessageQueue\Sender;

use Magento\Framework\Exception\ValidatorException;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\MessageQueue\Sender\Data\Customer as CustomerSender;
use Synerise\Integration\MessageQueue\Sender\Data\Order as OrderSender;
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
use Synerise\Integration\Observer\NewsletterSubscriberSaveAfter;
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
     * @var OrderSender
     */
    private $orderSender;

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
        OrderSender $orderSender,
        ProductSender $productSender,
        SubscriberSender $subscriberSender
    ) {
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
        $this->customerSender = $customerSender;
        $this->orderSender = $orderSender;
        $this->productSender = $productSender;
        $this->subscriberSender = $subscriberSender;
    }

    /**
     * @param $event_name
     * @param $payload
     * @param int $storeId
     * @param int|null $entityId
     * @param int|null $timeout
     * @return void
     * @throws ApiException
     * @throws ValidatorException
     * @throws \Synerise\CatalogsApiClient\ApiException
     */
    public function send($event_name, $payload, int $storeId, int $entityId = null, int $timeout = null)
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
                case OrderPlace::EVENT:
                    $this->orderSender->createATransaction($payload, $storeId, $timeout);
                    if ($entityId) {
                        $this->orderSender->markItemsAsSent([$entityId]);
                    }
                    break;
                case CatalogProductDeleteBefore::EVENT:
                    $this->productSender->addItemsBatchWithCatalogCheck($payload, $storeId);
                    break;
                case WishlistAddProduct::EVENT:
                    $apiInstance->clientAddedProductToFavorites('4.4', $payload);
                    break;
                case NewsletterSubscriberDeleteAfter::EVENT:
                case NewsletterSubscriberSaveAfter::EVENT:
                    $this->subscriberSender->batchAddOrUpdateClients($payload, $storeId, $timeout);
                    if ($entityId) {
                        $this->subscriberSender->markSubscribersAsSent([$entityId]);
                    }
                    break;
                case 'ADD_OR_UPDATE_CLIENT':
                    $this->customerSender->batchAddOrUpdateClients($payload, $storeId, $timeout);
                    if ($entityId) {
                        $this->customerSender->markCustomersAsSent([$entityId], $storeId);
                    }
            }
        } catch (\Synerise\ApiClient\ApiException $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e, 'api_response_body' => $e->getResponseBody()]);
            throw $e;
        }
    }
}
