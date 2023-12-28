<?php

namespace Synerise\Integration\Helper;

use Magento\Catalog\Model\Product;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\ApiClient\Model\CustomeventRequest;

class Cart
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
     * @var Tracking
     */
    protected $trackingHelper;

    public function __construct(
        StoreManagerInterface $storeManager,
        Image $imageHelper,
        Tracking $trackingHelper
    ) {
        $this->storeManager = $storeManager;
        $this->imageHelper = $imageHelper;
        $this->trackingHelper = $trackingHelper;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $totalAmount
     * @param $totalQuantity
     * @return CustomeventRequest
     * @throws \Exception
     */
    public function prepareCartStatusEvent(\Magento\Quote\Model\Quote $quote, $totalAmount, $totalQuantity): CustomeventRequest
    {
        $params = [
            'source' => $this->trackingHelper->getSource(),
            'applicationName' => $this->trackingHelper->getApplicationName(),
            'storeId' => $this->trackingHelper->getStoreId(),
            'storeUrl' => $this->trackingHelper->getStoreBaseUrl(),
            'products' => $this->prepareProductsFromQuote($quote),
            'totalAmount' => $totalAmount,
            'totalQuantity' => $totalQuantity
        ];

        if($this->trackingHelper->shouldIncludeParams($this->trackingHelper->getStoreId()) && $this->trackingHelper->getCookieParams()) {
            $params['snrs_params'] = $this->trackingHelper->getCookieParams();
        }

        return new CustomeventRequest([
            'event_salt' => $this->trackingHelper->generateEventSalt(),
            'time' => $this->trackingHelper->getCurrentTime(),
            'action' => 'cart.status',
            'label' => 'CartStatus',
            'client' => $this->trackingHelper->prepareClientDataFromQuote($quote),
            'params' => $params
        ]);
    }

    /**
     * @param Product $product
     * @return array
     * @throws \Exception
     */
    public function prepareParamsFromQuoteProduct($product)
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
     * @param $quote
     * @return array
     * @throws \Exception
     */
    public function prepareProductsFromQuote($quote)
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
     * @param $item
     * @return array
     */
    private function prepareProductFromQuoteItem($item)
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
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrencyCode()
    {
        return $this->storeManager->getStore()->getCurrentCurrency()->getCode();
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return bool
     */
    public function hasItemDataChanges(\Magento\Quote\Model\Quote $quote)
    {
        return ($quote->dataHasChangedFor('subtotal') || $quote->dataHasChangedFor('items_qty'));
    }

}