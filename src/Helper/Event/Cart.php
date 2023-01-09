<?php

namespace Synerise\Integration\Helper\Event;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\ApiClient\Model\Client;
use Synerise\ApiClient\Model\ClientaddedproducttocartRequest;
use Synerise\ApiClient\Model\CustomeventRequest;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Data\Context as ContextHelper;
use Synerise\Integration\Helper\Data\Product;


class Cart extends AbstractEvent
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Product
     */
    protected $productHelper;

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
        Api $apiHelper,
        Product $productHelper,
        ContextHelper $contextHelper
    ) {
        $this->storeManager = $storeManager;
        $this->productHelper = $productHelper;

        parent::__construct($apiHelper, $contextHelper);
    }

    /**
     * @param Item $quoteItem
     * @param string $event
     * @param string|null $uuid
     * @return ClientaddedproducttocartRequest
     * @throws LocalizedException
     * @throws NoSuchEntityException
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
     * @return array
     */
    public function prepareCartStatusProducts(Quote $quote): array
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
    private function prepareCartStatusProduct(Item $item): array
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

        $categoryIds = $product->getCategoryIds();
        if ($categoryIds) {
            $params['categories'] = [];
            foreach ($categoryIds as $categoryId) {
                $params['categories'][] = $this->productHelper->getFormattedCategoryPath($categoryId);
            }
        }

        return $params;
    }

    /**
     * @param Item $item
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function prepareAddToCartParams(Item $item): array
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

        $categoryIds = $product->getCategoryIds();
        if ($categoryIds) {
            $params['categories'] = [];
            foreach ($categoryIds as $categoryId) {
                $params['categories'][] = $this->productHelper->getFormattedCategoryPath($categoryId);
            }

            if ($product->getCategoryId()) {
                $category = $this->productHelper->getFormattedCategoryPath($product->getCategoryId());
                if ($category) {
                    $params['category'] = $category;
                }
            }
        }

        if ($product->getImage()) {
            $params['image'] = $this->productHelper->getOriginalImageUrl($product->getImage());
        }

        return $params;
    }

    public function sendAddToCartEvent(ClientaddedproducttocartRequest $request)
    {
        return $this->apiHelper->getDefaultApiInstance()
            ->clientAddedProductToCartWithHttpInfo('4.4', $request);
    }

    public function sendRemoveFromCartEvent(ClientaddedproducttocartRequest $request)
    {
        return $this->apiHelper->getDefaultApiInstance()
            ->clientRemovedProductFromCartWithHttpInfo('4.4', $request);
    }

    public function sendCartStatusEvent(CustomeventRequest $customEventRequest, $force = false)
    {
        $response = null;
        if (!$this->cartStatusSent || $force) {
            $response = $this->apiHelper->getDefaultApiInstance()
                ->customEventWithHttpInfo('4.4', $customEventRequest);
            $this->cartStatusSent = true;
        }

        return $response;
    }

    /**
     * @param Quote $quote
     * @return bool
     */
    public function hasItemDataChanges(Quote $quote)
    {
        return $quote->dataHasChangedFor('subtotal')
            || $quote->dataHasChangedFor('items_qty')
            || $quote->dataHasChangedFor('reserved_order_id');
    }
    
    /**
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCurrencyCode()
    {
        if (!isset($this->currentCurrencyCode)) {
            $this->currentCurrencyCode = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        }
        return $this->currentCurrencyCode;
    }
}