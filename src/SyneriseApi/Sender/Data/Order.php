<?php

namespace Synerise\Integration\SyneriseApi\Sender\Data;

use Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Synerise\ApiClient\Api\DefaultApi;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateatransactionRequest;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Product\Category;
use Synerise\Integration\Helper\Product\Image;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\Helper\Tracking\UuidGenerator;
use Synerise\Integration\SyneriseApi\Sender\AbstractSender;
use Synerise\Integration\SyneriseApi\ConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory;

class Order extends AbstractSender implements SenderInterface
{
    public const MODEL = 'order';

    public const ENTITY_ID = 'entity_id';

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var RuleRepositoryInterface
     */
    protected $ruleRepository;

    /**
     * @var Category
     */
    protected $categoryHelper;

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
     * @var UuidGenerator
     */
    protected $uuidGenerator;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ResourceConnection $resource
     * @param RuleRepositoryInterface $ruleRepository
     * @param ConfigFactory $configFactory
     * @param InstanceFactory $apiInstanceFactory
     * @param Category $categoryHelper
     * @param Cookie $cookieHelper
     * @param Image $imageHelper
     * @param Logger $loggerHelper
     * @param Tracking $trackingHelper
     * @param UuidGenerator $uuidGenerator
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ResourceConnection $resource,
        RuleRepositoryInterface $ruleRepository,
        ConfigFactory $configFactory,
        InstanceFactory  $apiInstanceFactory,
        Category $categoryHelper,
        Cookie $cookieHelper,
        Image  $imageHelper,
        Logger $loggerHelper,
        Tracking  $trackingHelper,
        UuidGenerator  $uuidGenerator
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->resource = $resource;
        $this->ruleRepository = $ruleRepository;
        $this->categoryHelper = $categoryHelper;
        $this->cookieHelper = $cookieHelper;
        $this->imageHelper = $imageHelper;
        $this->trackingHelper = $trackingHelper;
        $this->uuidGenerator = $uuidGenerator;

        parent::__construct($loggerHelper, $configFactory, $apiInstanceFactory);
    }

    /**
     * Send items
     *
     * @param Collection|OrderModel[] $collection
     * @param int $storeId
     * @param int|null $websiteId
     * @return void
     * @throws ApiException
     * @throws ValidatorException
     */
    public function sendItems($collection, int $storeId, ?int $websiteId = null)
    {
        $ids = $createATransactionRequest = [];

        foreach ($collection as $order) {
            $ids[] = $order->getEntityId();

            $email = $order->getCustomerEmail();
            $uuid = $email ? $this->uuidGenerator->generateByEmail($email) : null;

            $params = $this->prepareOrderParams($order, $uuid);
            if ($params) {
                $createATransactionRequest[] = new CreateatransactionRequest($params);
            }
        }

        if (!empty($createATransactionRequest)) {
            $this->batchAddOrUpdateTransactions(
                $createATransactionRequest,
                $storeId
            );
        }

        if ($ids) {
            $this->markItemsAsSent($ids);
        }
    }

    /**
     * Batch add or update transactions
     *
     * @param mixed $payload
     * @param int $storeId
     * @throws ApiException
     * @throws ValidatorException
     */
    public function batchAddOrUpdateTransactions($payload, int $storeId)
    {
        try {
            list ($body, $statusCode, $headers) = $this->sendWithTokenExpiredCatch(
                function () use ($storeId, $payload) {
                    $this->getDefaultApiInstance($storeId)
                        ->batchAddOrUpdateTransactionsWithHttpInfo('4.4', $payload);
                },
                $storeId
            );

            if ($statusCode == 207) {
                $this->loggerHelper->getLogger()->warning('Request partially accepted', ['response' => $body]);
            }
        } catch (ApiException $e) {
            $this->logApiException($e);
            throw $e;
        }
    }

    /**
     * Get default API instance
     *
     * @param int $storeId
     * @return DefaultApi
     * @throws ApiException
     * @throws ValidatorException
     */
    protected function getDefaultApiInstance(int $storeId): DefaultApi
    {
        return $this->getApiInstance('default', $storeId);
    }

    /**
     * Prepare order params
     *
     * @param OrderModel $order
     * @param string|null $uuid
     * @return array
     * @throws Exception
     */
    public function prepareOrderParams(OrderModel $order, ?string $uuid = null): array
    {
        $context = $this->trackingHelper->getContext();
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

            if (!$item->getProduct() && $this->isSent($order->getEntityId())) {
                $this->loggerHelper->getLogger()->warning(
                    sprintf('Product not found & order %s already sent, skip update', $order->getIncrementId())
                );

                return [];
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
                'applicationName' => $context->getApplicationName(),
                'storeId' => $order->getStoreId(),
                'storeUrl' => $context->getStoreBaseUrl($order->getStoreId())
            ],
            'order_id' => $order->getRealOrderId(),
            "payment_info" => [
                "method" => $order->getPayment()->getMethod()
            ],
            "products" => $products,
            'recorded_at' => $order->getCreatedAt() ?
                $context->formatDateTimeAsIso8601(new \DateTime($order->getCreatedAt())) :
                $context->getCurrentTime(),
            'revenue' => [
                "amount" => (float) $order->getSubTotal(),
                "currency" => $order->getOrderCurrencyCode()
            ],
            'value' => [
                "amount" => (float) $order->getSubTotal(),
                "currency" => $order->getOrderCurrencyCode()
            ],
            'source' => $context->getSource(),
            'event_salt' => $order->getRealOrderId()
        ];

        if ($this->cookieHelper->shouldIncludeSnrsParams($order->getStoreId())) {
            $snrs_params = $this->cookieHelper->getSnrsParams();
            if ($snrs_params) {
                $params['snrs_params'] = $snrs_params;
            }
        }

        $orderRules = $this->prepareRulesList((string) $order->getAppliedRuleIds());
        if (!empty($orderRules)) {
            $params['metadata']['promotionRules'] = $orderRules;
        }

        return $params;
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

        if ($this->cookieHelper->shouldIncludeSnrsParams($storeId)) {
            $snrs_params = $this->cookieHelper->getSnrsParams();
            if ($snrs_params) {
                $params['snrs_params'] = $snrs_params;
            }
        }

        if ($storeId) {
            $params["storeId"] = $storeId;
            $params["storeUrl"] = $this->trackingHelper->getContext()->getStoreBaseUrl($storeId);
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
            $this->loggerHelper->getLogger()->error($e);
        }

        return $rules;
    }

    /**
     * Check if order is sent
     *
     * @param int $orderId
     * @return string
     */
    public function isSent(int $orderId): string
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select();
        $tableName = $connection->getTableName('synerise_sync_order');

        $select->from($tableName, ['synerise_updated_at'])
            ->where('order_id = ?', $orderId);

        return $connection->fetchOne($select);
    }

    /**
     * Mark orders as sent
     *
     * @param string[] $ids
     * @return void
     */
    public function markItemsAsSent(array $ids)
    {
        $data = [];
        foreach ($ids as $id) {
            $data[] = [
                'order_id' => $id
            ];
        }
        $this->resource->getConnection()->insertOnDuplicate(
            $this->resource->getConnection()->getTableName('synerise_sync_order'),
            $data
        );
    }

    /**
     * @inheritDoc
     */
    public function getAttributesToSelect(int $storeId): array
    {
        return[];
    }
}
