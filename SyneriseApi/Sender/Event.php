<?php

namespace Synerise\Integration\SyneriseApi\Sender;

use InvalidArgumentException;
use Magento\Framework\Exception\ValidatorException;
use Synerise\ApiClient\Api\DefaultApi;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Observer\Data\CustomerDelete;
use Synerise\Integration\Observer\Event\CartAddProduct;
use Synerise\Integration\Observer\Event\CartQtyUpdate;
use Synerise\Integration\Observer\Event\CartRemoveProduct;
use Synerise\Integration\Observer\Event\CartStatus;
use Synerise\Integration\Observer\Event\CustomerLogin;
use Synerise\Integration\Observer\Event\CustomerLogout;
use Synerise\Integration\Observer\Event\CustomerRegister;
use Synerise\Integration\Observer\Event\ProductReview;
use Synerise\Integration\Observer\Event\WishlistAddProduct;
use Synerise\Integration\Observer\Event\WishlistRemoveProduct;

class Event extends AbstractSender
{
    /**
     * Send
     *
     * @param string $event_name
     * @param mixed $payload
     * @param int $storeId
     * @return void
     * @throws ApiException
     * @throws ValidatorException
     * @throws \Exception
     */
    public function send(
        string $event_name,
        $payload,
        int $storeId
    ) {
        switch ($event_name) {
            case CartAddProduct::EVENT:
                $requestCallback = function () use ($storeId, $payload) {
                    $this->getDefaultApiInstance($storeId)->clientAddedProductToCart('4.4', $payload);
                };
                break;
            case CartRemoveProduct::EVENT:
                $requestCallback = function () use ($storeId, $payload) {
                    $this->getDefaultApiInstance($storeId)->clientRemovedProductFromCart('4.4', $payload);
                };
                break;
            case CartQtyUpdate::EVENT:
            case CartStatus::EVENT:
            case CustomerDelete::EVENT:
            case ProductReview::EVENT:
            case WishlistRemoveProduct::EVENT:
                $requestCallback = function () use ($storeId, $payload) {
                    $this->getDefaultApiInstance($storeId)->customEvent('4.4', $payload);
                };
                break;
            case CustomerRegister::EVENT:
                $requestCallback = function () use ($storeId, $payload) {
                    $this->getDefaultApiInstance($storeId)->clientRegistered('4.4', $payload);
                };
                break;
            case CustomerLogin::EVENT:
                $requestCallback = function () use ($storeId, $payload) {
                    $this->getDefaultApiInstance($storeId)->clientLoggedIn('4.4', $payload);
                };
                break;
            case CustomerLogout::EVENT:
                $requestCallback = function () use ($storeId, $payload) {
                    $this->getDefaultApiInstance($storeId)->clientLoggedOut('4.4', $payload);
                };
                break;
            case WishlistAddProduct::EVENT:
                $requestCallback = function () use ($storeId, $payload) {
                    $this->getDefaultApiInstance($storeId)->clientAddedProductToFavorites('4.4', $payload);
                };
                break;
            default:
                throw new InvalidArgumentException(
                    'Unexpected event: '. json_encode([$event_name, $payload, $storeId])
                );
        }

        try {
            $this->sendWithTokenExpiredCatch($requestCallback, $storeId);
        } catch (ApiException $e) {
            $this->logApiException($e);
            throw $e;
        }
    }

    /**
     * Get Default API instance
     *
     * @param int $storeId
     * @return DefaultApi
     * @throws ApiException
     * @throws ValidatorException
     */
    protected function getDefaultApiInstance(int $storeId): DefaultApi
    {
        return $this->getApiInstance('default', $storeId);
    }
}
