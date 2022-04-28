<?php

namespace Synerise\Integration\Helper;

use Magento\Catalog\Helper\Image;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateatransactionRequest;

class Order extends \Magento\Framework\App\Helper\AbstractHelper
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

    public function __construct(
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Cache\Manager $cacheManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Newsletter\Model\Subscriber $subscriber,
        StoreManagerInterface $storeManager,
        Api $apiHelper,
        Catalog $catalogHelper,
        Tracking $trackingHelper
    ) {
        $this->addressRepository = $addressRepository;
        $this->subscriber= $subscriber;
        $this->storeManager = $storeManager;
        $this->categoryRepository = $categoryRepository;
        $this->resource = $resource;
        $this->cacheManager = $cacheManager;
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
        $this->dateTime = $dateTime;
        $this->apiHelper = $apiHelper;
        $this->catalogHelper = $catalogHelper;
        $this->trackingHelper = $trackingHelper;

        parent::__construct($context);
    }

    /**
     * @param $collection
     * @throws ApiException
     */
    public function addOrdersBatch($collection)
    {
        if (!$collection->getSize()) {
            return;
        }

        $ids = [];
        $createatransaction_request = [];

        if (!$collection->count()) {
            return;
        }

        foreach ($collection as $order) {
            $ids[] = $order->getEntityId();

            $email = $order->getCustomerEmail();
            $uuid = $email ? $this->trackingHelper->genrateUuidByEmail($email): null;

            $params = $this->preapreOrderParams($order, $uuid);
            if ($params) {
                $createatransaction_request[] = new CreateatransactionRequest($params);
            }
        }

        if (!empty($createatransaction_request)) {
            $this->sendOrdersToSynerise($createatransaction_request, $ids);
        }
    }

    /**
     * @param $createatransaction_request
     * @param $ids
     * @throws ApiException
     */
    public function sendOrdersToSynerise($createatransaction_request, $entityIds)
    {
        list ($body, $statusCode, $headers) = $this->apiHelper->getDefaultApiInstance()
            ->batchAddOrUpdateTransactionsWithHttpInfo('4.4', $createatransaction_request);

        if (substr($statusCode, 0, 1) != 2) {
            throw new ApiException(sprintf('Invalid Status [%d]', $statusCode));
        } elseif ($statusCode == 207) {
            $this->_logger->debug('Request accepted with errors', ['response' => $body]);
        }
    }

    public function preapreOrderParams(\Magento\Sales\Model\Order $order, $uuid = null)
    {
        $customerData = [
            'email' => $order->getCustomerEmail()
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
                $this->_logger->debug(
                    sprintf('Product not found & order %s already sent, skip update', $order->getIncrementId())
                );

                return [];
            }

            $products[] = $this->prepareProductParamsFromOrderItem($item, $order->getOrderCurrencyCode());
        }

        $params = [
            'client' => $customerData,
            "discountAmount" => [
                "amount" => $order->getDiscountAmount(),
                "currency" => $order->getOrderCurrencyCode()
            ],
            'metadata' => [
                "orderStatus" => $order->getStatus(),
                "discountCode" => $order->getCouponCode()
            ],
            'order_id' => $order->getRealOrderId(),
            "paymentInfo" => [
                "method" => $order->getPayment()->getMethod()
            ],
            "products" => $products,
            'recorded_at' =>
                $order->getCreatedAt() ?
                    $this->trackingHelper->formatDateTimeAsIso8601(new \DateTime($order->getCreatedAt())) :
                    $this->trackingHelper->getCurrentTime(),
            'revenue' => [
                "amount" => $order->getSubTotal(),
                "currency" => $order->getOrderCurrencyCode()
            ],
            'value' => [
                "amount" => $order->getSubTotal(),
                "currency" => $order->getOrderCurrencyCode()
            ],
            'source' => $this->trackingHelper->getSource(),
            'event_salt'  => $order->getRealOrderId()
        ];

        return $params;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderItemInterface $item
     * @param string $currency
     * @return array
     */
    public function prepareProductParamsFromOrderItem($item, $currency)
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

    public function getAttributes()
    {
        $attributes = $this->scopeConfig->getValue(
            \Synerise\Integration\Helper\Config::XML_PATH_CUSTOMERS_ATTRIBUTES
        );

        return $attributes ? explode(',', $attributes) : [];
    }

    public function getAttributesToSelect()
    {
        return '*';
    }

    public function getDefaultWebsiteCode()
    {
        try {
            $websiteCode = $this->storeManager->getWebsite()->getCode();
        } catch (LocalizedException $localizedException) {
            $websiteCode = null;
            $this->_logger->error($localizedException->getMessage());
        }
        return $websiteCode;
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
