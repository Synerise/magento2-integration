<?php

namespace Synerise\Integration\SyneriseApi\Sender\Data;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\Api\DefaultApi;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateatransactionRequest;
use Synerise\Integration\Helper\Category;
use Synerise\Integration\Helper\Image;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\SyneriseApi\Sender\AbstractSender;
use Synerise\Integration\SyneriseApi\ConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory;

class Order extends AbstractSender implements SenderInterface
{
    const MODEL = 'order';
    const ENTITY_ID = 'entity_id';

    const MAX_PAGE_SIZE = 100;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var RuleRepositoryInterface
     */
    protected $ruleRepository;

    /**
     * @var Category
     */
    protected $categoryHelper;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ResourceConnection $resource,
        RuleRepositoryInterface $ruleRepository,
        LoggerInterface $logger,
        ConfigFactory $configFactory,
        InstanceFactory $apiInstanceFactory,
        Category $categoryHelper,
        Image $imageHelper,
        Tracking $trackingHelper
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->resource = $resource;
        $this->ruleRepository = $ruleRepository;
        $this->logger = $logger;
        $this->categoryHelper = $categoryHelper;
        $this->imageHelper = $imageHelper;
        $this->trackingHelper = $trackingHelper;

        parent::__construct($logger, $configFactory, $apiInstanceFactory);
    }

    /**
     * @param Collection|OrderModel[] $collection
     * @param int $storeId
     * @param int|null $websiteId
     * @return void
     * @throws ApiException
     * @throws ValidatorException
     * @throws NoSuchEntityException
     */
    public function sendItems($collection, int $storeId, ?int $websiteId = null)
    {
        $ids = $createATransactionRequest = [];

        foreach ($collection as $order) {
            $ids[] = $order->getEntityId();

            $email = $order->getCustomerEmail();
            $uuid = $email ? $this->trackingHelper->generateUuidByEmail($email) : null;

            $params = $this->preapreOrderParams($order, $uuid);
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
     * @param $payload
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
                $this->logger->warning('Request partially accepted', ['response' => $body]);
            }
        } catch (ApiException $e) {
            $this->logApiException($e);
            throw $e;
        }
    }

    /**
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
     * @param \Magento\Sales\Model\Order $order
     * @param $uuid
     * @return array
     * @throws NoSuchEntityException
     */
    public function preapreOrderParams(\Magento\Sales\Model\Order $order, $uuid = null)
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
                $this->trackingHelper->shouldIncludeParams($order->getStoreId()) ? $this->trackingHelper->getCookieParams() : null
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

        $snrs_params = $this->trackingHelper->getCookieParams();
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
     * @param OrderItemInterface $item
     * @param string $currency
     * @param int|null $storeId
     * @param string[]|null $snrs_params
     * @return array
     * @throws NoSuchEntityException
     */
    public function prepareProductParamsFromOrderItem($item, $currency, $storeId = null, $snrs_params = null)
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

        if ($snrs_params) {
            $params['snrs_params'] = $snrs_params;
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
