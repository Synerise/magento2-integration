<?php

namespace Synerise\Integration\SyneriseApi\Mapper\Data;

use Exception;
use Magento\Catalog\Helper\Data;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Synerise\ApiClient\Model\CreateatransactionRequest;
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
     * @return CreateatransactionRequest
     * @throws Exception
     */
    public function prepareRequest(Order $order, ?string $uuid = null): CreateatransactionRequest
    {
        $shippingAddress = $order->getShippingAddress();

        $customerData = [
            'email' => $order->getCustomerEmail(),
            'phone' => $shippingAddress ? $shippingAddress->getTelephone() : null
        ];

        if ($uuid) {
            $customerData["uuid"] = $uuid;
        }

        if (!$order->getCustomerIsGuest()) {
            $customerData['customId'] = $order->getCustomerId();
        }

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
                $order->getStoreId()
            );
        }

        $params = [
            'client' => $customerData,
            "discount_amount" => [
                "amount" => (float) $order->getDiscountAmount(),
                "currency" => $order->getOrderCurrencyCode()
            ],
            'metadata' => [
                "orderStatus" => $order->getStatus(),
                "discountCode" => $order->getCouponCode(),
                "shipping" => [
                    'method' => $order->getShippingMethod(),
                    'amount' => (float) $order->getShippingAmount()
                ],
                'applicationName' => $this->contextHelper->getApplicationName(),
                'storeId' => $order->getStoreId(),
                'storeUrl' => $this->contextHelper->getStoreBaseUrl($order->getStoreId())
            ],
            'order_id' => $order->getRealOrderId(),
            "payment_info" => [
                "method" => $order->getPayment()->getMethod()
            ],
            "products" => $products,
            'recorded_at' => $order->getCreatedAt() ?
                $this->contextHelper->formatDateTimeAsIso8601(new \DateTime($order->getCreatedAt())) :
                $this->contextHelper->getCurrentTime(),
            'revenue' => [
                "amount" => $this->getOrderSubtotal($order, $order->getStoreId()),
                "currency" => $order->getOrderCurrencyCode()
            ],
            'value' => [
                "amount" => (float) $order->getSubTotal(),
                "currency" => $order->getOrderCurrencyCode()
            ],
            'source' => $this->contextHelper->getSource(),
            'event_salt' => $order->getRealOrderId()
        ];

        $snrs_params = $this->cookieHelper->shouldIncludeSnrsParams($order->getStoreId()) ?
            $this->cookieHelper->getSnrsParams() : null;
        if ($snrs_params) {
            $params['snrs_params'] = $snrs_params;
        }

        $orderRules = $this->prepareRulesList((string) $order->getAppliedRuleIds());
        if (!empty($orderRules)) {
            $params['metadata']['promotionRules'] = $orderRules;
        }

        return new CreateatransactionRequest($params);
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
        ?int $storeId = null
    ): array {
        $product = $item->getProduct();

        $regularPrice = [
            "amount" => $this->priceHelper->getPrice($product, $item->getOriginalPrice(), $storeId),
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

        $snrs_params = $this->cookieHelper->shouldIncludeSnrsParams($storeId) ?
            $this->cookieHelper->getSnrsParams() : null;
        if ($snrs_params) {
            $params['snrs_params'] = $snrs_params;
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
        if ($this->priceHelper->calculateTax($storeId)) {
            return (float) $order->getSubtotalInclTax();
        } else {
            return (float) $order->getSubtotal();
        }
    }
}
