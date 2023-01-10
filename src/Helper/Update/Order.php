<?php

namespace Synerise\Integration\Helper\Update;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\Order as OrderModel;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\Client;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\ApiClient\Model\CreateatransactionRequest;
use Synerise\ApiClient\Model\CreateatransactionRequestDiscountAmount;
use Synerise\ApiClient\Model\CreateatransactionRequestRevenue;
use Synerise\ApiClient\Model\CreateatransactionRequestValue;
use Synerise\ApiClient\Model\PaymentInfo;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Data\Product;
use Synerise\Integration\Helper\Data\Context as ContextHelper;

class Order extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var RuleRepositoryInterface
     */
    protected $ruleRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var Api
     */
    protected $apiHelper;

    /**
     * @var ContextHelper
     */
    protected $contextHelper;

    /**
     * @var Product
     */
    private $productHelper;

    public function __construct(
        ResourceConnection $resource,
        Context $context,
        DateTime $dateTime,
        RuleRepositoryInterface $ruleRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Api $apiHelper,
        ContextHelper $contextHelper,
        Product $productHelper
    ) {
        $this->resource = $resource;
        $this->dateTime = $dateTime;
        $this->ruleRepository = $ruleRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->apiHelper = $apiHelper;
        $this->contextHelper = $contextHelper;
        $this->productHelper = $productHelper;

        parent::__construct($context);
    }

    /**
     * @param $createTransactionRequest
     * @param $ids
     * @throws ApiException
     */
    public function sendBatchAddOrUpdateTransactions($createTransactionRequest, $storeId)
    {
        list ($body, $statusCode, $headers) = $this->apiHelper->getDefaultApiInstance($storeId)
            ->batchAddOrUpdateTransactionsWithHttpInfo('4.4', $createTransactionRequest);

        if (substr($statusCode, 0, 1) != 2) {
            throw new ApiException(sprintf('Invalid Status [%d]', $statusCode));
        } elseif ($statusCode == 207) {
            $this->_logger->debug('Request accepted with errors', ['response' => $body]);
        }
    }

    /**
     * @param $createTransactionRequest
     * @param $storeId
     * @return array
     * @throws ApiException
     */
    public function sendCreateTransaction($createTransactionRequest, $storeId = null)
    {
        list ($body, $statusCode, $headers) = $this->apiHelper->getDefaultApiInstance($storeId)
            ->createATransactionWithHttpInfo('4.4', $createTransactionRequest);

        if (substr($statusCode, 0, 1) != 2) {
            throw new ApiException(sprintf('Invalid Status [%d]', $statusCode));
        } elseif ($statusCode == 207) {
            $this->_logger->debug('Request accepted with errors', ['response' => $body]);
        }

        return [$body, $statusCode, $headers];
    }

    /**
     * @param CreateaClientinCRMRequest $createAClientInCrmRequest
     * @param $storeId
     * @return array
     * @throws ApiException
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    public function sendCreateClient(CreateaClientinCRMRequest $createAClientInCrmRequest, $storeId = null): array
    {
        return $this->apiHelper->getDefaultApiInstance($storeId)
            ->createAClientInCrmWithHttpInfo('4.4', $createAClientInCrmRequest);
    }

    /**
     * @param $order
     * @param $uuid
     * @return CreateaClientinCRMRequest
     */
    public function prepareCreateClientRequest($order, $uuid)
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
     * @param OrderModel $order
     * @param $uuid
     * @return CreateatransactionRequest|null
     * @throws \Exception
     */
    public function prepareCreateTransactionRequest(OrderModel $order, $uuid = null): ?CreateatransactionRequest
    {
        $products = [];
        foreach ($order->getAllItems() as $item) {
            if ($item->getParentItem()) {
                continue;
            }

            if (!$item->getProduct() && $this->isSent($order->getEntityId())) {
                $this->_logger->debug(
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
            'discount_amount' => new CreateatransactionRequestDiscountAmount([
                "amount" => $order->getDiscountAmount(),
                "currency" => $order->getOrderCurrencyCode()
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
                'method' => $order->getPayment()->getMethod()
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

        $orderRules = $this->prepareRulesList((string) $order->getAppliedRuleIds());
        if (!empty($orderRules)) {
            $params['metadata']['promotionRules'] = $orderRules;
        }

        return new CreateatransactionRequest($params);
    }

    /**
     * @param $orderId
     * @return string
     */
    public function isSent($orderId)
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select();
        $tableName = $connection->getTableName('synerise_sync_order');

        $select->from($tableName, ['synerise_updated_at'])
            ->where('order_id = ?', $orderId);

        return $connection->fetchOne($select);
    }

    /**
     * @param $ids
     * @return void
     */
    public function markItemsAsSent($ids)
    {
        $timestamp = $this->dateTime->gmtDate();
        $data = [];
        foreach ($ids as $id) {
            $data[] = [
                'synerise_updated_at' => $timestamp,
                'order_id' => $id
            ];
        }
        $this->resource->getConnection()->insertOnDuplicate(
            $this->resource->getConnection()->getTableName('synerise_sync_order'),
            $data
        );
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderItemInterface $item
     * @param string $currency
     * @return array
     */
    protected function prepareProduct($item, $currency)
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
            "quantity" => $item->getQtyOrdered()
        ];

        $itemRules = $this->prepareRulesList((string) $item->getAppliedRuleIds());
        if(!empty($itemRules)){
            $params["promotionRules"] = $itemRules;
        }

        if ($product) {
            $params['url'] = $product->setStoreId($item->getStoreId())->getUrlInStore();

            $categoryIds = $product->getCategoryIds();
            if ($categoryIds) {
                $params['categories'] = [];
                foreach ($categoryIds as $categoryId) {
                    $params['categories'][] = $this->productHelper->getFormattedCategoryPath($categoryId);
                }
            }

            if ($product->getImage()) {
                $params['image'] = $this->productHelper->getOriginalImageUrl($product->getImage());
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

            /**
             * @var \Magento\SalesRule\Api\Data\RuleInterface[] $rulesList
             */
            $rulesList = $this->ruleRepository->getList($searchCriteria)->getItems();
            foreach($rulesList as $rule){
                $rules[] = $rule->getName();
            }
        } catch (\Exception $e){
            $this->_logger->error($e->getMessage(), [$e]);
        }

        return $rules;
    }
}
