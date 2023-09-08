<?php

namespace Synerise\Integration\Helper;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateatransactionRequest;

class Order
{
    protected $configWriter;
    protected $cacheManager;
    protected $dateTime;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Api
     */
    protected $apiHelper;

    /**
     * @var Catalog
     */
    private $catalogHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    protected $categoryRepository;

    protected $formattedCategoryPaths = [];

    protected $addressRepository;

    protected $subscriber;

    protected $resource;

    /**
     * @var RuleRepositoryInterface
     */
    protected $ruleRepository;

    protected $searchCriteriaBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        LoggerInterface $logger,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\App\Cache\Manager $cacheManager,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Newsletter\Model\Subscriber $subscriber,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RuleRepositoryInterface $ruleRepository,
        StoreManagerInterface $storeManager,
        Api $apiHelper,
        Catalog $catalogHelper,
        Tracking $trackingHelper
    ) {
        $this->logger = $logger;
        $this->addressRepository = $addressRepository;
        $this->subscriber= $subscriber;
        $this->storeManager = $storeManager;
        $this->categoryRepository = $categoryRepository;
        $this->resource = $resource;
        $this->cacheManager = $cacheManager;
        $this->configWriter = $configWriter;
        $this->dateTime = $dateTime;
        $this->apiHelper = $apiHelper;
        $this->catalogHelper = $catalogHelper;
        $this->trackingHelper = $trackingHelper;
        $this->ruleRepository = $ruleRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param $collection
     * @param $storeId
     * @return array
     * @throws ApiException
     */
    public function addOrdersBatch($collection, $storeId)
    {
        if (!$collection->getSize()) {
            return[];
        }

        $ids = [];
        $createatransaction_request = [];

        if (!$collection->count()) {
            return[];
        }

        foreach ($collection as $order) {
            $ids[] = $order->getEntityId();

            $email = $order->getCustomerEmail();
            $uuid = $email ? $this->trackingHelper->generateUuidByEmail($email) : null;

            $params = $this->preapreOrderParams($order, $uuid);
            if ($params) {
                $createatransaction_request[] = new CreateatransactionRequest($params);
            }
        }

        if (!empty($createatransaction_request)) {
            $this->sendOrdersToSynerise(
                $createatransaction_request,
                $storeId,
                $this->apiHelper->getScheduledRequestTimeout($storeId)
            );
        }
        return $ids;
    }

    /**
     * @param $createatransaction_request
     * @param $ids
     * @throws ApiException
     */
    public function sendOrdersToSynerise($createatransaction_request, $storeId, $timeout = null)
    {
        list ($body, $statusCode, $headers) = $this->apiHelper->getDefaultApiInstance($storeId, $timeout)
            ->batchAddOrUpdateTransactionsWithHttpInfo('4.4', $createatransaction_request);

        if (substr($statusCode, 0, 1) != 2) {
            throw new ApiException(sprintf('Invalid Status [%d]', $statusCode));
        } elseif ($statusCode == 207) {
            $this->logger->warning('Request partially accepted', ['response' => $body]);
        }
    }

    public function preapreOrderParams(\Magento\Sales\Model\Order $order, $uuid = null)
    {
        $shippingAddress = $order->getShippingAddress();
        $phone = null;
        if ($shippingAddress) {
            $phone = $shippingAddress->getTelephone();
        }

        $customerData = [
            'email' => $order->getCustomerEmail(),
            'phone' => $phone
        ];

        if ($uuid) {
            $customerData["uuid"] = $uuid;
        }

        if (!$order->getCustomerIsGuest()) {
            $customerData['customId'] = $order->getCustomerId();
        }

        $snrs_params = $this->trackingHelper->getCookieParams();

        $products = [];
        foreach ($order->getAllItems() as $item) {
            if ($item->getParentItem()) {
                continue;
            }

            if (!$item->getProduct() && $this->isSent($order->getEntityId())) {
                $this->logger->warning(
                    sprintf('Product not found & order %s already sent, skip update', $order->getIncrementId())
                );

                return [];
            }

            $products[] = $this->prepareProductParamsFromOrderItem(
                $item,
                $order->getOrderCurrencyCode(),
                $order->getStoreId(),
                $snrs_params
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
                'applicationName' => $this->trackingHelper->getApplicationName(),
                'storeId' => $order->getStoreId(),
                'storeUrl' => $this->trackingHelper->getStoreBaseUrl($order->getStoreId())
            ],
            'order_id' => $order->getRealOrderId(),
            "payment_info" => [
                "method" => $order->getPayment()->getMethod()
            ],
            "products" => $products,
            'recorded_at' => $order->getCreatedAt() ?
                    $this->trackingHelper->formatDateTimeAsIso8601(new \DateTime($order->getCreatedAt())) :
                    $this->trackingHelper->getCurrentTime(),
            'revenue' => [
                "amount" => (float) $order->getSubTotal(),
                "currency" => $order->getOrderCurrencyCode()
            ],
            'value' => [
                "amount" => (float) $order->getSubTotal(),
                "currency" => $order->getOrderCurrencyCode()
            ],
            'source' => $this->trackingHelper->getSource(),
            'event_salt' => $order->getRealOrderId()
        ];

        if ($this->trackingHelper->shouldIncludeParams($order->getStoreId()) && $snrs_params) {
            $params['metadata']['snrs_params'] = $snrs_params;
        }

        $orderRules = $this->prepareRulesList((string) $order->getAppliedRuleIds());
        if (!empty($orderRules)) {
            $params['metadata']['promotionRules'] = $orderRules;
        }

        return $params;
    }

    /**
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

            /**
             * @var \Magento\SalesRule\Api\Data\RuleInterface[] $rulesList
             */
            $rulesList = $this->ruleRepository->getList($searchCriteria)->getItems();
            foreach ($rulesList as $rule) {
                $rules[] = $rule->getName();
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }

        return $rules;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderItemInterface $item
     * @param string $currency
     * @return array
     */
    public function prepareProductParamsFromOrderItem($item, $currency, $storeId = null, $_snrs_p = null)
    {
        $product = $item->getProduct();

        $regularPrice = [
            "amount" => (float) $item->getOriginalPrice(),
            "currency" => $currency
        ];

        $finalUnitPrice = [
            "amount" => (float) $item->getPrice() - ((float) $item->getDiscountAmount() / $item->getQtyOrdered()),
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
        
        if($this->trackingHelper->shouldIncludeParams($this->trackingHelper->getStoreId()) && $this->trackingHelper->getCookieParams()) {
            $params['snrs_params'] = $this->trackingHelper->getCookieParams();
        }

        if ($storeId) {
            $params["storeId"] = $storeId;
            $params["storeUrl"] = $this->trackingHelper->getStoreBaseUrl($storeId);
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
                    $params['categories'][] = $this->catalogHelper->getFormattedCategoryPath($categoryId);
                }
            }

            if ($product->getImage()) {
                $params['image'] = $this->catalogHelper->getOriginalImageUrl($product->getImage());
            }
        }

        if ($sku!= $skuVariant) {
            $params['skuVariant'] = $skuVariant;
        }

        return $params;
    }

    public function getAttributesToSelect()
    {
        return '*';
    }

    public function isSent($orderId)
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select();
        $tableName = $connection->getTableName('synerise_sync_order');

        $select->from($tableName, ['synerise_updated_at'])
            ->where('order_id = ?', $orderId);

        return $connection->fetchOne($select);
    }

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
}
