<?php

namespace Synerise\Integration\SyneriseApi\Mapper\Event;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Synerise\ApiClient\Model\Client;
use Synerise\ApiClient\Model\CustomeventRequest;
use Synerise\Integration\Helper\Product\Price;
use Synerise\Integration\Helper\Tracking\Context;
use Synerise\Integration\Helper\Tracking\UuidGenerator;

class CartStatus
{
    /**
     * @var Context
     */
    protected $contextHelper;

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
     * @param Price $priceHelper
     * @param UuidGenerator $uuidGenerator
     */
    public function __construct(
        Context $contextHelper,
        Price $priceHelper,
        UuidGenerator $uuidGenerator
    ) {
        $this->contextHelper = $contextHelper;
        $this->priceHelper = $priceHelper;
        $this->uuidGenerator = $uuidGenerator;
    }

    /**
     * Prepare cart status event from quote
     *
     * @param Quote $quote
     * @param string|null $uuid
     * @param array|null $cookieParams
     * @param bool $emptyCart
     * @return CustomeventRequest
     */
    public function prepareRequest(
        Quote $quote,
        ?string $uuid = null,
        ?array $cookieParams = null,
        bool $emptyCart = false
    ): CustomeventRequest {
        $params = $this->contextHelper->prepareContextParams();
        $params['products'] = !$emptyCart ? $this->prepareProductsFromQuote($quote) : [];
        $params['totalAmount'] = !$emptyCart ? $this->getQuoteSubtotal($quote, $quote->getStoreId()): 0;
        $params['totalQuantity'] = !$emptyCart ? (int) $quote->getItemsQty() : 0;

        if ($cookieParams) {
            $params['snrs_params'] = $cookieParams;
        }

        return new CustomeventRequest([
            'event_salt' => $this->contextHelper->generateEventSalt(),
            'time' => $this->contextHelper->getCurrentTime(),
            'action' => 'cart.status',
            'label' => 'CartStatus',
            'client' => $this->prepareClientDataFromQuote($quote, $uuid),
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
     * Get quote subtotal including tax if enabled by config
     *
     * @param Quote $quote
     * @param int $storeId
     * @return float
     */
    public function getQuoteSubtotal(Quote $quote, int $storeId): float
    {
        if ($this->priceHelper->includeTax($storeId)) {
            $totals = $quote->getTotals();
            return isset($totals['subtotal']) ? (float) $totals['subtotal']->getValue() : $quote->getSubtotal();
        } else {
            return $quote->getSubtotal();
        }
    }
}
