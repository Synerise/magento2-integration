<?php

namespace Synerise\Integration\SyneriseApi\Sender;

use InvalidArgumentException;
use Magento\Framework\Exception\ValidatorException;
use Synerise\ApiClient\Api\DefaultApi;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Observer\CartAddProduct;
use Synerise\Integration\Observer\CartQtyUpdate;
use Synerise\Integration\Observer\CartRemoveProduct;
use Synerise\Integration\Observer\CartStatus;
use Synerise\Integration\Observer\CustomerLogin;
use Synerise\Integration\Observer\CustomerLogout;
use Synerise\Integration\Observer\CustomerRegister;
use Synerise\Integration\Observer\ProductReview;
use Synerise\Integration\Observer\WishlistAddProduct;
use Synerise\Integration\Observer\WishlistRemoveProduct;

class Event extends AbstractSender
{
    /**
     * @param string $event_name
     * @param $payload
     * @param int $storeId
     * @return void
     * @throws ApiException
     * @throws ValidatorException
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
                throw new InvalidArgumentException('Unexpected event: '. json_encode([$event_name, $payload, $storeId]));
        }

        try {
            $this->sendWithTokenExpiredCatch($requestCallback, $storeId);
        } catch (ApiException $e) {
            $this->logApiException($e);
            throw $e;
        }
    }

    /**
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