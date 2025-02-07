<?php

namespace Synerise\Integration\SyneriseApi\Mapper\Data;

use Exception;
use Magento\Catalog\Helper\Data;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Synerise\ApiClient\Model\Client;
use Synerise\ApiClient\Model\CreateatransactionRequest;
use Synerise\ApiClient\Model\CreateatransactionRequestDiscountAmount;
use Synerise\ApiClient\Model\CreateatransactionRequestRevenue;
use Synerise\ApiClient\Model\CreateatransactionRequestValue;
use Synerise\ApiClient\Model\PaymentInfo;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Product\Category;
use Synerise\Integration\Helper\Product\Image;
use Synerise\Integration\Helper\Product\Price;
use Synerise\Integration\Helper\Tracking\Context;
use Synerise\Integration\Helper\Tracking\Cookie;

class OrderCRUD
{

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var RuleRepositoryInterface
     */
    protected $ruleRepository;

    /**
     * @var Data
     */
    protected $taxHelper;

    /**
     * @var Category
     */
    protected $categoryHelper;

    /**
     * @var Context
     */
    protected $contextHelper;

    /**
     * @var Cookie
     */
    protected $cookieHelper;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var Price
     */
    protected $priceHelper;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RuleRepositoryInterface $ruleRepository
     * @param Category $categoryHelper
     * @param Context $contextHelper
     * @param Cookie $cookieHelper
     * @param Image $imageHelper
     * @param Logger $loggerHelper
     * @param Price $priceHelper
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RuleRepositoryInterface $ruleRepository,
        Category $categoryHelper,
        Context $contextHelper,
        Cookie $cookieHelper,
        Image $imageHelper,
        Logger $loggerHelper,
        Price $priceHelper
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->ruleRepository = $ruleRepository;
        $this->priceHelper = $priceHelper;
        $this->categoryHelper = $categoryHelper;
        $this->contextHelper = $contextHelper;
        $this->cookieHelper = $cookieHelper;
        $this->imageHelper = $imageHelper;
        $this->loggerHelper = $loggerHelper;
    }

    /**
     * Prepare order params
     *
     * @param Order $order
     * @param string|null $uuid
     * @param array $options
     * @return CreateatransactionRequest
     * @throws Exception
     */
    public function prepareRequest(Order $order, ?string $uuid = null, array $options = []): CreateatransactionRequest
    {
        $snrsParams = isset($options['snrs_params']) && is_array($options['snrs_params']) ?
            $options['snrs_params'] : [];

        $products = [];
        foreach ($order->getAllItems() as $item) {
            if ($item->getParentItem()) {
                continue;
            }

            if (!$item->getProduct()) {
                throw new NotFoundException(__('Product not found for order %1', $order->getIncrementId()));
            }

            $products[] = $this->prepareProductParamsFromOrderItem(
                $item,
                $order->getOrderCurrencyCode(),
                $order->getStoreId(),
                $snrsParams
            );
        }

        $params = [
            'client' => $this->prepareClient($order, $uuid),
            'metadata' => $this->prepareMetadata($order),
            'order_id' => $order->getRealOrderId(),
            'payment_info' => $this->preparePaymentInfo($order),
            'products' => $products,
            'recorded_at' => $order->getCreatedAt() ?
                $this->contextHelper->formatDateTimeAsIso8601(new \DateTime($order->getCreatedAt())) :
                $this->contextHelper->getCurrentTime(),
            'revenue' => $this->prepareRevenue(
                $this->getOrderSubtotal($order, $order->getStoreId()),
                $order->getOrderCurrencyCode()
            ),
            'value' => $this->prepareValue(
                (float) $order->getSubTotal(),
                $order->getOrderCurrencyCode()
            ),
            'event_salt' => $order->getRealOrderId()
        ];

        if (!empty($options['source'])) {
            $params['source'] = $options['source'];
        }

        if ($order->getDiscountAmount()) {
            $params['discount_amount'] = $this->prepareDiscount(
                (float) $order->getDiscountAmount(),
                $order->getOrderCurrencyCode()
            );
        }

        if (!empty($snrsParams) && $this->cookieHelper->shouldIncludeSnrsParams($order->getStoreId())) {
            $params['metadata']['snrs_params'] = $snrsParams;
        }

        return new CreateatransactionRequest($params);
    }

    /**
     * Prepare client data
     *
     * @param Order $order
     * @param string|null $uuid
     * @return Client
     */
    public function prepareClient(Order $order, ?string $uuid = null): Client
    {
        $shippingAddress = $order->getShippingAddress();

        $customerData = [
            'email' => $order->getCustomerEmail(),
            'phone' => $shippingAddress ? $shippingAddress->getTelephone() : null
        ];

        if ($uuid) {
            $customerData['uuid'] = $uuid;
        }

        if (!$order->getCustomerIsGuest()) {
            $customerData['customId'] = $order->getCustomerId();
        }

        return new Client($customerData);
    }

    /**
     * Prepare product params from order item
     *
     * @param OrderItemInterface $item
     * @param string $currency
     * @param int|null $storeId
     * @return array
     */
    public function prepareProductParamsFromOrderItem(
        OrderItemInterface $item,
        string $currency,
        ?int $storeId = null,
        $snrsParams = null
    ): array {
        $product = $item->getProduct();

        $regularPrice = [
            "amount" => $this->priceHelper->getTaxPrice($product, $item->getOriginalPrice(), $storeId),
            "currency" => $currency
        ];

        $finalUnitPrice = [
            "amount" => $this->priceHelper->getFinalUnitPrice($item, $storeId),
            "currency" => $currency
        ];

        $skuVariant = $item->getSku();
        if ($item->getProductType() == 'configurable') {
            $sku = $product ? $product->getSku() : 'N/A';
        } else {
            $sku = $item->getSku();
        }

        $params = [
            "sku" => $sku,
            "name" => $item->getName(),
            "regularPrice" => $regularPrice,
            "finalUnitPrice" => $finalUnitPrice,
            "quantity" => $item->getQtyOrdered()
        ];

        if ($this->cookieHelper->shouldIncludeSnrsParams($storeId)) {
            $params['snrs_params'] = $snrsParams;
        }

        if ($storeId) {
            $params["storeId"] = $storeId;
            $params["storeUrl"] = $this->contextHelper->getStoreBaseUrl($storeId);
        }

        $itemRules = $this->prepareRulesList((string) $item->getAppliedRuleIds());
        if (!empty($itemRules)) {
            $params["promotionRules"] = $itemRules;
        }

        if ($product) {
            $params['url'] = $product->setStoreId($item->getStoreId())->getUrlInStore();

            $categoryIds = $product->getCategoryIds();
            if ($categoryIds) {
                $params['categories'] = [];
                foreach ($categoryIds as $categoryId) {
                    $params['categories'][] = $this->categoryHelper->getFormattedCategoryPath($categoryId);
                }
            }

            if ($product->getImage()) {
                $params['image'] = $this->imageHelper->getOriginalImageUrl($product->getImage());
            }
        }

        if ($sku!= $skuVariant) {
            $params['skuVariant'] = $skuVariant;
        }

        return $params;
    }

    /**
     * Prepare order discount data
     *
     * @param float $amount
     * @param string $currency
     * @return CreateatransactionRequestDiscountAmount
     */
    public function prepareDiscount(float $amount, string $currency): CreateatransactionRequestDiscountAmount
    {
        return new CreateatransactionRequestDiscountAmount([
            'amount' => $amount,
            'currency' => $currency
        ]);
    }

    /**
     * Prepare order revenue data
     *
     * @param float $amount
     * @param string $currency
     * @return CreateatransactionRequestRevenue
     */
    public function prepareRevenue(float $amount, string $currency): CreateatransactionRequestRevenue
    {
        return new CreateatransactionRequestRevenue([
            'amount' => $amount,
            'currency' => $currency
        ]);
    }

    /**
     * Prepare order value data
     *
     * @param float $amount
     * @param string $currency
     * @return CreateatransactionRequestValue
     */
    public function prepareValue(float $amount, string $currency): CreateatransactionRequestValue
    {
        return new CreateatransactionRequestValue([
            'amount' => $amount,
            'currency' => $currency
        ]);
    }

    /**
     * Prepare order payment info
     *
     * @param Order $order
     * @return PaymentInfo|null
     */
    public function preparePaymentInfo(Order $order): ?PaymentInfo
    {
        if ($order->getPayment()) {
            return new PaymentInfo(['method' => $order->getPayment()->getMethod()]);
        }

        return null;
    }

    /**
     * Prepare order metadata
     *
     * @param Order $order
     * @return array
     */
    public function prepareMetadata(Order $order): array
    {
        $metadata = [
            'orderStatus' => $order->getStatus(),
            'discountCode' => $order->getCouponCode(),
            'shipping' => [
                'method' => $order->getShippingMethod(),
                'amount' => $this->getShippingAmount($order, $order->getStoreId())
            ],
            'applicationName' => $this->contextHelper->getApplicationName(),
            'storeId' => $order->getStoreId(),
            'storeUrl' => $this->contextHelper->getStoreBaseUrl($order->getStoreId())
        ];

        $orderRules = $this->prepareRulesList((string) $order->getAppliedRuleIds());
        if (!empty($orderRules)) {
            $metadata['promotionRules'] = $orderRules;
        }

        return $metadata;
    }

    /**
     * Prepare rule list
     *
     * @param string $appliedRuleIds
     * @return array
     */
    public function prepareRulesList(string $appliedRuleIds): array
    {
        $rules = [];
        if (empty($appliedRuleIds)) {
            return $rules;
        }

        try {
            $searchCriteria = $this->searchCriteriaBuilder->addFilter(
                'rule_id',
                explode(',', $appliedRuleIds),
                'in'
            )->create();

            $rulesList = $this->ruleRepository->getList($searchCriteria)->getItems();
            foreach ($rulesList as $rule) {
                $rules[] = $rule->getName();
            }
        } catch (Exception $e) {
            $this->loggerHelper->error($e);
        }

        return $rules;
    }

    /**
     * Get order subtotal including tax if enabled by config
     *
     * @param Order $order
     * @param int $storeId
     * @return float
     */
    public function getOrderSubtotal(Order $order, int $storeId): float
    {
        if ($this->priceHelper->includeTax($storeId)) {
            return (float) $order->getSubtotalInclTax();
        } else {
            return (float) $order->getSubtotal();
        }
    }

    /**
     * Get order subtotal including tax if enabled by config
     *
     * @param Order $order
     * @param int $storeId
     * @return float
     */
    public function getShippingAmount(Order $order, int $storeId): float
    {
        if ($this->priceHelper->includeTax($storeId)) {
            return (float) $order->getShippingInclTax();
        } else {
            return (float) $order->getShippingAmount();
        }
    }
}
