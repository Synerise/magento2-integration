<?php

namespace Synerise\Integration\Helper;

use Exception;
use Magento\Framework\Exception\ValidatorException;
use Magento\Store\Model\ScopeInterface;
use Synerise\ApiClient\Api\DefaultApi;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Api\Factory\DefaultApiFactory;
use Synerise\Integration\Helper\Synchronization\Results;
use Synerise\Integration\Helper\Synchronization\Sender\Customer as CustomerSender;
use Synerise\Integration\Helper\Synchronization\Sender\Order as OrderSender;
use Synerise\Integration\Observer\Event\Cart\AddProduct as CartAddProduct;
use Synerise\Integration\Observer\Event\Cart\QtyUpdate as CartQtyUpdate;
use Synerise\Integration\Observer\Event\Cart\RemoveProduct as CartRemoveProduct;
use Synerise\Integration\Observer\Event\Cart\Status as CartStatus;
use Synerise\Integration\Observer\Event\Customer\Login as CustomerLogin;
use Synerise\Integration\Observer\Event\Customer\Logout as CustomerLogout;
use Synerise\Integration\Observer\Event\Customer\Register as CustomerRegister;
use Synerise\Integration\Observer\Event\ProductReview;
use Synerise\Integration\Observer\Event\Wishlist\AddProduct as WishlistAddProduct;
use Synerise\Integration\Observer\Update\OrderPlace;

class Event extends \Magento\Framework\App\Helper\AbstractHelper
{
    const ADD_OR_UPDATE_CLIENT = 'ADD_OR_UPDATE_CLIENT';
    const BATCH_ADD_OR_UPDATE_CLIENT = 'BATCH_ADD_OR_UPDATE_CLIENT';

    /**
     * @var \Synerise\Integration\Helper\Api
     */
    private $apiHelper;

    /**
     * @var Results
     */
    protected $resultsHelper;

    /**
     * @var DefaultApiFactory
     */
    protected $defaultApiFactory;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Synerise\Integration\Helper\Api $apiHelper,
        Results $resultsHelper,
        DefaultApiFactory $defaultApiFactory
    ) {
        parent::__construct($context);
        $this->apiHelper = $apiHelper;
        $this->resultsHelper = $resultsHelper;
        $this->defaultApiFactory = $defaultApiFactory;
    }

    /**
     * @param string $eventName
     * @param mixed $payload
     * @param int $storeId
     * @param int|null $entityId
     * @param int|null $timeout
     * @return array
     * @throws ApiException
     * @throws ValidatorException
     * @throws Exception
     */
    public function sendEvent(string $eventName, $payload, int $storeId, int $entityId = null, int $timeout = null): array
    {
        try {
            $apiInstance = $this->getDefaultApiInstance($storeId, $timeout);

            switch ($eventName) {
                case CartAddProduct::EVENT:
                    return $apiInstance->clientAddedProductToCartWithHttpInfo('4.4', $payload);
                case CartRemoveProduct::EVENT:
                    return $apiInstance->clientRemovedProductFromCartWithHttpInfo('4.4', $payload);
                case CartQtyUpdate::EVENT:
                case CartStatus::EVENT:
                case ProductReview::EVENT:
                    return $apiInstance->customEventWithHttpInfo('4.4', $payload);
                case CustomerRegister::EVENT:
                    return $apiInstance->clientRegisteredWithHttpInfo('4.4', $payload);
                case CustomerLogin::EVENT:
                    return $apiInstance->clientLoggedInWithHttpInfo('4.4', $payload);
                case CustomerLogout::EVENT:
                    return $apiInstance->clientLoggedOutWithHttpInfo('4.4', $payload);
                case OrderPlace::EVENT:
                    $response = $apiInstance->createATransactionWithHttpInfo('4.4', $payload);
                    if ($entityId) {
                        $this->resultsHelper->markAsSent(OrderSender::MODEL, [$entityId]);
                    }
                    return $response;
                case WishlistAddProduct::EVENT:
                    return $apiInstance->clientAddedProductToFavoritesWithHttpInfo('4.4', $payload);
                case self::ADD_OR_UPDATE_CLIENT:
                    list($body, $statusCode, $headers) = $apiInstance
                        ->createAClientInCrmWithHttpInfo('4.4', $payload );
                    if ($statusCode != 202) {
                        $this->_logger->error('Client update failed');
                    } elseif ($entityId) {
                        $this->resultsHelper->markAsSent(CustomerSender::MODEL, [$entityId], $storeId);
                    }
                    return [$body, $statusCode, $headers];
                case self::BATCH_ADD_OR_UPDATE_CLIENT:
                    list($body, $statusCode, $headers) = $apiInstance
                        ->batchAddOrUpdateClientsWithHttpInfo('application/json','4.4', $payload);
                    return [$body, $statusCode, $headers];
                default:
                    throw new Exception('Failed to send event. Invalid Event Name');
            }
        } catch (ApiException $e) {
            $this->_logger->error('Synerise Error', ['exception' => $e, 'api_response_body' => $e->getResponseBody()]);
            throw $e;
        }
    }

    /**
     * @param int|null $storeId
     * @param int|null $timeout
     * @return DefaultApi
     * @throws ApiException
     * @throws ValidatorException
     */
    public function getDefaultApiInstance(?int $storeId = null, ?int $timeout = null): DefaultApi
    {
        return $this->defaultApiFactory->get(
            $this->apiHelper->getApiConfigByScope(
                $storeId,
                ScopeInterface::SCOPE_STORE,
                $timeout ?: $this->apiHelper->getScheduledRequestTimeout($storeId)
            )
        );
    }
}
