<?php

namespace Synerise\Integration\SyneriseApi\Mapper\Event;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Synerise\ApiClient\Model\Client;
use Synerise\ApiClient\Model\ClientaddedproducttocartRequest;
use Synerise\Integration\Helper\Product\Image;
use Synerise\Integration\Helper\Product\Price;
use Synerise\Integration\Helper\Tracking\Context;
use Synerise\Integration\Helper\Tracking\UuidGenerator;

class CartAddRemove
{
    /**
     * @var Context
     */
    protected $contextHelper;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var Price
     */
    protected $priceHelper;

    /**
     * @var UuidGenerator
     */
    protected $uuidGenerator;

    /**
     * @param Context $contextHelper
     * @param Image $imageHelper
     * @param Price $priceHelper
     * @param UuidGenerator $uuidGenerator
     */
    public function __construct(
        Context $contextHelper,
        Image $imageHelper,
        Price $priceHelper,
        UuidGenerator $uuidGenerator
    ) {
        $this->contextHelper = $contextHelper;
        $this->imageHelper = $imageHelper;
        $this->priceHelper = $priceHelper;
        $this->uuidGenerator = $uuidGenerator;
    }

    /**
     * Prepare client added product to cart request
     *
     * @param string $eventName
     * @param Item $quoteItem
     * @param string|null $uuid
     * @param array|null $cookieParams
     * @return ClientaddedproducttocartRequest
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function prepareRequest(
        string $eventName,
        Item $quoteItem,
        ?string $uuid = null,
        ?array $cookieParams = null
    ): ClientaddedproducttocartRequest {
        $params = array_merge(
            $this->contextHelper->prepareContextParams(),
            $this->prepareParamsFromQuoteProduct($quoteItem)
        );

        if ($cookieParams) {
            $params['snrs_params'] = $cookieParams;
        }

        return new ClientaddedproducttocartRequest([
            'event_salt' => $this->contextHelper->generateEventSalt(),
            'time' => $this->contextHelper->getCurrentTime(),
            'label' => $this->contextHelper->getEventLabel($eventName),
            'client' => $this->prepareClientDataFromQuote($quoteItem->getQuote(), $uuid),
            'params' => $params
        ]);
    }

    /**
     * Prepare client data from quote object
     *
     * @param Quote $quote
     * @param string|null $uuid
     * @return Client
     */
    public function prepareClientDataFromQuote(Quote $quote, ?string $uuid = null): Client
    {
        $data['uuid'] = $uuid;

        if ($quote->getCustomerEmail()) {
            $data['email'] = $quote->getCustomerEmail();
            $data['uuid'] = $this->uuidGenerator->generateByEmail($data['email']);

            if ($quote->getCustomerId()) {
                $data['custom_id'] = $quote->getCustomerId();
            }
        }

        return new Client($data);
    }

    /**
     * Prepare products data from quote item product object
     *
     * @param Product $product
     * @param int|null $storeId
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function prepareParamsFromQuoteProduct(Item $quoteItem): array
    {
        $product = $quoteItem->getProduct();
        $storeId = $quoteItem->getStoreId();

        $currencyCode = $this->contextHelper->getCurrencyCode($storeId);
        $sku = $product->getData('sku');
        $skuVariant = $product->getSku();

        $params = [
            "sku" => $sku,
            "name" => $product->getName(),
            "productUrl" => $product->getUrlInStore(),
            "quantity" => $quoteItem->getQty() ?: $product->getData('cart_qty')
        ];

        if ($sku!= $skuVariant) {
            $params['skuVariant'] = $skuVariant;
        }

        if ($product->getFinalPrice()) {
            $params['finalUnitPrice'] = [
                "amount" => $this->priceHelper->getPrice($product, $product->getFinalPrice(), $storeId),
                "currency" => $currencyCode
            ];
        }

        if ($product->getPrice()) {
            $params['regularUnitPrice'] = [
                "amount" => $this->priceHelper->getPrice($product, $product->getPrice(), $storeId),
                "currency" => $currencyCode
            ];
        }

        if ($product->getSpecialPrice()) {
            $params['discountedUnitPrice'] = [
                "amount" => $this->priceHelper->getPrice($product, $product->getSpecialPrice(), $storeId),
                "currency" => $currencyCode
            ];
        }

        if ($product->getImage()) {
            $params['image'] = $this->imageHelper->getOriginalImageUrl($product->getImage());
        }

        return $params;
    }
}
