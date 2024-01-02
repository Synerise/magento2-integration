<?php

namespace Synerise\Integration\Helper;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\ApiClient\Model\CustomeventRequest;
use Synerise\Integration\Helper\Product\Image;
use Synerise\Integration\Helper\Tracking\Cookie;

class Cart
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Cookie
     */
    protected $cookieHelper;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Cookie $cookieHelper
     * @param Image $imageHelper
     * @param Tracking $trackingHelper
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Cookie $cookieHelper,
        Image $imageHelper,
        Tracking $trackingHelper
    ) {
        $this->storeManager = $storeManager;
        $this->cookieHelper = $cookieHelper;
        $this->imageHelper = $imageHelper;
        $this->trackingHelper = $trackingHelper;
    }

    /**
     * Prepare cart status event from quote
     *
     * @param Quote $quote
     * @param float $totalAmount
     * @param float $totalQuantity
     * @return CustomeventRequest
     * @throws Exception
     */
    public function prepareCartStatusEvent(Quote $quote, float $totalAmount, float $totalQuantity): CustomeventRequest
    {
        $params = $this->trackingHelper->prepareContextParams();
        $params['products'] = $this->prepareProductsFromQuote($quote);
        $params['totalAmount'] = $totalAmount;
        $params['totalQuantity'] = $totalQuantity;

        $cookieParams = $this->getCookieParams();
        if ($cookieParams) {
            $params['snrs_params'] = $cookieParams;
        }

        return new CustomeventRequest([
            'event_salt' => $this->trackingHelper->generateEventSalt(),
            'time' => $this->trackingHelper->getContext()->getCurrentTime(),
            'action' => 'cart.status',
            'label' => 'CartStatus',
            'client' => $this->trackingHelper->prepareClientDataFromQuote($quote),
            'params' => $params
        ]);
    }

    /**
     * Prepare products data from quote item product object
     *
     * @param Product $product
     * @return array
     * @throws Exception
     */
    public function prepareParamsFromQuoteProduct(Product $product): array
    {
        $sku = $product->getData('sku');
        $skuVariant = $product->getSku();

        $params = [
            "sku" => $sku,
            "name" => $product->getName(),
            "regularUnitPrice" => [
                "amount" => (float) $product->getPrice(),
                "currency" => $this->getCurrencyCode()
            ],
            "finalUnitPrice" => [
                "amount" => (float) $product->getFinalPrice(),
                "currency" => $this->getCurrencyCode()
            ],
            "productUrl" => $product->getUrlInStore(),
            "quantity" => $product->getQty()
        ];

        if ($sku!= $skuVariant) {
            $params['skuVariant'] = $skuVariant;
        }

        if ($product->getSpecialPrice()) {
            $params['discountedUnitPrice'] = [
                "amount" => (float) $product->getSpecialPrice(),
                "currency" => $this->getCurrencyCode()
            ];
        }

        if ($product->getImage()) {
            $params['image'] = $this->imageHelper->getOriginalImageUrl($product->getImage());
        }

        return $params;
    }

    /**
     * Prepare products data from quote object
     *
     * @param Quote $quote
     * @return array
     */
    public function prepareProductsFromQuote(Quote $quote): array
    {
        $products = [];
        $items = $quote->getAllVisibleItems();
        if (is_array($items)) {
            foreach ($items as $item) {
                $products[] = $this->prepareProductFromQuoteItem($item);
            }
        }

        return $products;
    }

    /**
     * Prepare product data from quote item object
     *
     * @param Item $item
     * @return array
     */
    private function prepareProductFromQuoteItem(Item $item): array
    {
        $product = $item->getProduct();

        $sku = $product->getData('sku');
        $skuVariant = $item->getSku();

        $params = [
            "sku" => $sku,
            "quantity" => $item->getQty()
        ];

        if ($sku!= $skuVariant) {
            $params['skuVariant'] = $skuVariant;
        }

        return $params;
    }

    /**
     * Get currency code of current store
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCurrencyCode(): string
    {
        return $this->storeManager->getStore()->getCurrentCurrency()->getCode();
    }

    /**
     * Check if cart was actually updated
     *
     * @param Quote $quote
     * @return bool
     */
    public function hasItemDataChanges(Quote $quote): bool
    {
        return ($quote->dataHasChangedFor('subtotal') || $quote->dataHasChangedFor('items_qty'));
    }

    /**
     * Get Cookie Params if enabled by config
     *
     * @return array
     */
    public function getCookieParams(): array
    {
        return ($this->cookieHelper->shouldIncludeSnrsParams()) ? $this->cookieHelper->getSnrsParams() : [];
    }
}
