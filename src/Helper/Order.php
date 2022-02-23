<?php

namespace Synerise\Integration\Helper;

use Magento\Catalog\Helper\Image;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Api
     */
    protected $apiHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var Image
     */
    protected $imageHelper;

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
        LoggerInterface $logger,
        Api $apiHelper,
        Tracking $trackingHelper,
        Image $imageHelper
    ) {
        $this->addressRepository = $addressRepository;
        $this->subscriber= $subscriber;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->categoryRepository = $categoryRepository;
        $this->resource = $resource;
        $this->cacheManager = $cacheManager;
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
        $this->dateTime = $dateTime;
        $this->apiHelper = $apiHelper;
        $this->trackingHelper = $trackingHelper;
        $this->imageHelper = $imageHelper;

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

            $createatransaction_request[] = new \Synerise\ApiClient\Model\CreateatransactionRequest(
                $this->preapreOrderParams($order)
            );
        }

        $this->sendOrdersToSynerise($createatransaction_request, $ids);
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
            throw new ApiException(sprintf(
                'Invalid Status [%d]',
                $statusCode
            ));
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
            if (!$item->getParentItem()) {
                $products[] = $this->prepareProductParamsFromOrderItem($item, $order->getOrderCurrencyCode());
            }
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
        $parent = $item->getParentItem();

        if ($parent && (float) $item->getOriginalPrice() == '0.00') {
            $regularPrice = [
                "amount" => $parent->getOriginalPrice(),
                "currency" => $currency
            ];
        } else {
            $regularPrice = [
                "amount" => $item->getOriginalPrice(),
                "currency" => $currency
            ];
        }

        if ($parent && (float) $item->getPrice() == '0.00') {
            $finalUnitPrice = [
                "amount" => $parent->getPrice() - ($item->getDiscountAmount() / $item->getQtyOrdered()),
                "currency" => $currency
            ];
        } else {
            $finalUnitPrice = [
                "amount" => $item->getPrice() - ($item->getDiscountAmount() / $item->getQtyOrdered()),
                "currency" => $currency
            ];
        }

        $sku = $product->getSku();
        $skuVariant = $item->getSku();

        $params = [
            "sku" => $sku,
            "name" => $item->getName(),
            "regularPrice" => $regularPrice,
            "finalUnitPrice" => $finalUnitPrice,
            "url" => $item->getUrlInStore(),
            "quantity" => $item->getQtyOrdered()
        ];

        if ($sku!= $skuVariant) {
            $params['skuVariant'] = $skuVariant;
        }

        $categories = $item->getCategoryIds();
        if ($categories) {
            $params['categories'] = $categories;
        }

        if ($product->getImage()) {
            $imageUrl = $this->imageHelper->init($product, 'product_base_image')
                ->setImageFile($product->getImage())->getUrl();
            if ($imageUrl) {
                $params['image'] = $imageUrl;
            }
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
            $this->logger->error($localizedException->getMessage());
        }
        return $websiteCode;
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
