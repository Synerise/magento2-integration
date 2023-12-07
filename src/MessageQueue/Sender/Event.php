<?php

namespace Synerise\Integration\MessageQueue\Sender;

use Magento\Framework\Exception\ValidatorException;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\CatalogsApiClient\ApiException as CatalogApiException;
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
use Synerise\Integration\SyneriseApi\ConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory;

class Event extends AbstractSender
{
    /**
     * @var ConfigFactory
     */
    protected $configFactory;

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
        InstanceFactory $apiInstanceFactory,
        ConfigFactory $configFactory,
        CustomerSender$customerSender,
        ProductSender $productSender,
        SubscriberSender $subscriberSender
    ) {
        $this->configFactory = $configFactory;
        $this->customerSender = $customerSender;
        $this->productSender = $productSender;
        $this->subscriberSender = $subscriberSender;

        parent::__construct($logger, $configFactory, $apiInstanceFactory);
    }

    /**
     * @param $event_name
     * @param $payload
     * @param int $storeId
     * @param int|null $entityId
     * @param int|null $timeout
     * @param bool $isRetry
     * @return void
     * @throws ApiException
     * @throws CatalogApiException
     * @throws ValidatorException
     */
    public function send($event_name, $payload, int $storeId, int $entityId = null, int $timeout = null, bool $isRetry = false)
    {
        try {
            $config = $this->configFactory->getConfig(ConfigFactory::MODE_SCHEDULE, $storeId);

            $apiInstance = $this->apiInstanceFactory->getApiInstance(
                $config->getScopeKey(),
                'default',
                $config
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
                    if($entityId) {
                        $this->productSender->deleteStatus([$entityId], $storeId);
                    }
                    break;
                case NewsletterSubscriberDeleteAfter::EVENT:
                    $this->subscriberSender->batchAddOrUpdateClients([$payload], $storeId, $timeout);
                    if($entityId) {
                        $this->subscriberSender->deleteStatus([$entityId]);
                    }
                    break;
                case OrderPlace::CUSTOMER_UPDATE:
                case ProductReview::CUSTOMER_UPDATE:
                    $this->customerSender->batchAddOrUpdateClients([$payload], $storeId, $timeout);
            }
        } catch (ApiException | CatalogApiException $e) {
            $this->handleApiExceptionAndMaybeUnsetToken($e, ConfigFactory::MODE_SCHEDULE, $storeId);
            if (!$isRetry) {
                $this->send($event_name, $payload, $storeId, $entityId, $timeout, true);
            }
        }
    }
}
