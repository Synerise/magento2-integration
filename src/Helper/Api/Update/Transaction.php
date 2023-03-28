<?php

namespace Synerise\Integration\Helper\Api\Update;

use Exception;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\Model\Client;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\ApiClient\Model\CreateatransactionRequest;
use Synerise\ApiClient\Model\CreateatransactionRequestDiscountAmount;
use Synerise\ApiClient\Model\CreateatransactionRequestRevenue;
use Synerise\ApiClient\Model\CreateatransactionRequestValue;
use Synerise\ApiClient\Model\PaymentInfo;
use Synerise\Integration\Helper\Api\Context;
use Synerise\Integration\Helper\Api\Update\Item\Image;
use Synerise\Integration\Helper\Synchronization\Results;
use Synerise\Integration\Helper\Synchronization\Sender\Order as OrderSender;

class Transaction
{
    /**
     * @var RuleRepositoryInterface
     */
    protected $ruleRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var LoggerInterface
     */
    protected $logger;
    
    /**
     * @var Context
     */
    protected $contextHelper;

    /**
     * @var Image
     */
    private $imageHelper;

    /**
     * @var Results
     */
    protected $resultsHelper;

    public function __construct(
        RuleRepositoryInterface $ruleRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger,
        Context $contextHelper,
        Image $imageHelper,
        Results $resultsHelper
    ) {
        $this->ruleRepository = $ruleRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
        $this->contextHelper = $contextHelper;
        $this->imageHelper = $imageHelper;
        $this->resultsHelper = $resultsHelper;
    }

    /**
     * @param OrderInterface $order
     * @param string $uuid
     * @return CreateaClientinCRMRequest
     */
    public function prepareCreateClientRequest(OrderInterface $order, string $uuid): CreateaClientinCRMRequest
    {
        $shippingAddress = $order->getShippingAddress();
        return new CreateaClientinCRMRequest(
            [
                'email' => $order->getCustomerEmail(),
                'uuid' => $uuid,
                'phone' => $shippingAddress ? $shippingAddress->getTelephone() : null,
                'first_name' => $order->getCustomerFirstname(),
                'last_name' => $order->getCustomerLastname(),
            ]
        );
    }

    /**
     * @param Order $order
     * @param $uuid
     * @return CreateatransactionRequest|null
     * @throws Exception
     */
    public function prepareCreateTransactionRequest(OrderInterface $order, $uuid = null): ?CreateatransactionRequest
    {
        $products = [];
        foreach ($order->getAllItems() as $item) {
            if ($item->getParentItem()) {
                continue;
            }

            if (!$item->getProduct() && $this->isSent($order->getEntityId())) {
                $this->logger->debug(
                    sprintf('Product not found & order %s already sent, skip update', $order->getIncrementId())
                );

                return null;
            }

            $products[] = $this->prepareProduct($item, $order->getOrderCurrencyCode());
        }

        $params = [
            'client' => new Client([
                'email' => $order->getCustomerEmail(),
                'custom_id' => $order->getCustomerId(),
                'uuid' => $uuid
            ]),
            'metadata' => [
                "orderStatus" => $order->getStatus(),
                "discountCode" => $order->getCouponCode(),
                "shipping" => [
                    'method' => $order->getShippingMethod(),
                    'amount' => $order->getShippingAmount()
                ],
                'applicationName' => $this->contextHelper->getApplicationName(),
                'storeId' => $order->getStoreId(),
                'storeUrl' => $this->contextHelper->getStoreBaseUrl($order->getStoreId())
            ],
            'order_id' => $order->getRealOrderId(),
            'payment_info' => new PaymentInfo([
                'method' => $order->getPayment() ? $order->getPayment()->getMethod() : null
            ]),
            'products' => $products,
            'recorded_at' =>
                $order->getCreatedAt() ?
                    $this->contextHelper->formatDateTimeAsIso8601(new \DateTime($order->getCreatedAt())) :
                    $this->contextHelper->getCurrentTime(),
            'revenue' => new CreateatransactionRequestRevenue([
                "amount" => $order->getSubTotal(),
                "currency" => $order->getOrderCurrencyCode()
            ]),
            'value' => new CreateatransactionRequestValue([
                "amount" => $order->getSubTotal(),
                "currency" => $order->getOrderCurrencyCode()
            ]),
            'source' => $this->contextHelper->getSource(),
            'event_salt' => $order->getRealOrderId()
        ];

        if ($order->getDiscountAmount()) {
            $params['discount_amount'] = new CreateatransactionRequestDiscountAmount([
                "amount" => $order->getDiscountAmount(),
                "currency" => $order->getOrderCurrencyCode()
            ]);
        } else {
            $params['discount_amount'] = null;
        }

        $orderRules = $this->prepareRulesList((string) $order->getAppliedRuleIds());
        if (!empty($orderRules)) {
            $params['metadata']['promotionRules'] = $orderRules;
        }

        return new CreateatransactionRequest($params);
    }

    /**
     * @param int $id
     * @return bool
     */
    protected function isSent(int $id): bool
    {
        return $this->resultsHelper->isSent(OrderSender::MODEL, $id);
    }

    /**
     * @param OrderItemInterface $item
     * @param string $currency
     * @return array
     */
    protected function prepareProduct(OrderItemInterface $item, string $currency): array
    {
        $product = $item->getProduct();

        $regularPrice = [
            "amount" => $item->getOriginalPrice(),
            "currency" => $currency
        ];

        $finalUnitPrice = [
            "amount" => $item->getPrice() - ($item->getDiscountAmount() / $item->getQtyOrdered()),
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
            "quantity" => $item->getQtyOrdered(),
            "applicationName" => $this->contextHelper->getApplicationName(),
            "storeId" => $item->getStoreId(),
            "storeUrl" => $this->contextHelper->getStoreBaseUrl($item->getStoreId())
        ];

        $itemRules = $this->prepareRulesList((string) $item->getAppliedRuleIds());
        if(!empty($itemRules)){
            $params["promotionRules"] = $itemRules;
        }

        if ($product) {
            $params['url'] = $product->setStoreId($item->getStoreId())->getUrlInStore();

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
     * @param string $appliedRuleIds
     * @return array
     */
    protected function prepareRulesList(string $appliedRuleIds): array
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
            foreach($rulesList as $rule){
                $rules[] = $rule->getName();
            }
        } catch (Exception $e){
            $this->logger->error($e->getMessage(), [$e]);
        }

        return $rules;
    }
}
