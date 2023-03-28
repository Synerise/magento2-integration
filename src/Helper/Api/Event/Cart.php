<?php

namespace Synerise\Integration\Helper\Api\Event;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\ApiClient\Model\Client;
use Synerise\ApiClient\Model\ClientaddedproducttocartRequest;
use Synerise\ApiClient\Model\CustomeventRequest;
use Synerise\Integration\Helper\Api\Context as ContextHelper;
use Synerise\Integration\Helper\Api\Update\Item\Image;


class Cart extends AbstractEvent
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var bool
     */
    protected $cartStatusSent = false;

    /**
     * @var string
     */
    protected $currentCurrencyCode;

    public function __construct(
        StoreManagerInterface $storeManager,
        Image $imageHelper,
        ContextHelper $contextHelper
    ) {
        $this->storeManager = $storeManager;
        $this->imageHelper = $imageHelper;

        parent::__construct($contextHelper);
    }

    /**
     * @param Item $quoteItem
     * @param string $event
     * @param string|null $uuid
     * @return ClientaddedproducttocartRequest
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function prepareAddToCartRequest(Item $quoteItem, string $event, ?string $uuid = null): ClientaddedproducttocartRequest
    {
        $quote = $quoteItem->getQuote();

        return new ClientaddedproducttocartRequest(
            $this->prepareEventData(
                $this->getEventLabel($event),
                new Client([
                    'uuid' => $uuid,
                    'email' => $quote->getCustomerEmail(),
                    'custom_id' => $quote->getCustomerId()
                ]),
                $this->prepareAddToCartParams($quoteItem)
            )
        );
    }

    /**
     * @param Quote $quote
     * @param $uuid
     * @return CustomeventRequest
     */
    public function prepareCartStatusRequest(Quote $quote, $uuid = null): CustomeventRequest
    {
        if ($quote->dataHasChangedFor('reserved_order_id')) {
            $params = [
                'products' => [],
                'totalAmount' => 0,
                'totalQuantity' => 0
            ];
        } else {
            $params = [
                'products' => $this->prepareCartStatusProducts($quote),
                'totalAmount' => $quote->getSubtotal(),
                'totalQuantity' => (int) $quote->getItemsQty()
            ];
        }

        return new CustomeventRequest(
            $this->prepareEventData(
                'CartStatus',
                new Client([
                    'uuid' => $uuid,
                    'email' => $quote->getCustomerEmail(),
                    'custom_id' => $quote->getCustomerId()
                ]),
                $params,
                'cart.status'
            )
        );
    }

    /**
     * @param Quote $quote
     * @return bool
     */
    public function hasItemDataChanges(Quote $quote): bool
    {
        return $quote->dataHasChangedFor('subtotal')
            || $quote->dataHasChangedFor('items_qty')
            || $quote->dataHasChangedFor('reserved_order_id');
    }

    /**
     * @return bool
     */
    public function isCartStatusSent(): bool
    {
        return $this->cartStatusSent;
    }

    /**
     * @param bool $cartStatusSent
     * @return void
     */
    public function setCartStatusSent(bool $cartStatusSent)
    {
        $this->cartStatusSent = $cartStatusSent;
    }

    /**
     * @param Quote $quote
     * @return array
     */
    protected function prepareCartStatusProducts(Quote $quote): array
    {
        $products = [];
        $items = $quote->getAllVisibleItems();
        if (is_array($items)) {
            foreach ($items as $item) {
                $products[] = $this->prepareCartStatusProduct($item);
            }
        }

        return $products;
    }

    /**
     * @param Item $item
     * @return array
     */
    protected function prepareCartStatusProduct(Item $item): array
    {
        $product = $item->getProduct();

        $sku = $product->getData('sku');
        $skuVariant = $item->getSku();

        $params = [
            "sku" => $sku,
            "name" => $product->getName(),
            "quantity" => $item->getQty()
        ];

        if ($sku != $skuVariant) {
            $params['skuVariant'] = $skuVariant;
        }

        return $params;
    }

    /**
     * @param Item $item
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function prepareAddToCartParams(Item $item): array
    {
        $product = $item->getProduct();
        $sku = $product->getData('sku');
        $skuVariant = $product->getSku();

        $params = [
            "sku" => $sku,
            "name" => $product->getName(),
            "regularUnitPrice" => [
                "amount" => (float)$product->getPrice(),
                "currency" => $this->getCurrencyCode()
            ],
            "finalUnitPrice" => [
                "amount" => (float)$product->getFinalPrice(),
                "currency" => $this->getCurrencyCode()
            ],
            "productUrl" => $product->getUrlInStore(),
            "quantity" => $product->getQty()
        ];

        if ($sku != $skuVariant) {
            $params['skuVariant'] = $skuVariant;
        }

        if ($product->getSpecialPrice()) {
            $params['discountedUnitPrice'] = [
                "amount" => (float)$product->getSpecialPrice(),
                "currency" => $this->getCurrencyCode()
            ];
        }

        if ($product->getImage()) {
            $params['image'] = $this->imageHelper->getOriginalImageUrl($product->getImage());
        }

        return $params;
    }
    
    /**
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function getCurrencyCode(): string
    {
        if (!isset($this->currentCurrencyCode)) {
            $this->currentCurrencyCode = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        }
        return $this->currentCurrencyCode;
    }
}