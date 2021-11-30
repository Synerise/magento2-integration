<?php

namespace Synerise\Integration\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Config\DataInterfaceFactory;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * API SECTION.
     */
    const XML_PATH_API_KEY = 'synerise/api/key';
    const XML_PATH_API_LOGGER_ENABLED = 'synerise/api/logger_enabled';

    /**
     * PAGE TRACKING SECTION.
     */
    const XML_PATH_PAGE_TRACKING_ENABLED = 'synerise/page_tracking/enabled';
    const XML_PATH_PAGE_TRACKING_KEY = 'synerise/page_tracking/script';
    const XML_PATH_PAGE_TRACKING_OPENGRAPH = 'synerise/page_tracking/opengraph';

    /**
     * EVENT TRACKING SECTION.
     */
    const XML_PATH_EVENT_TRACKING_ENABLED = 'synerise/event_tracking/enabled';
    const XML_PATH_EVENT_TRACKING_EVENTS = 'synerise/event_tracking/events';

    /**
     * PRODUCTS SECTION
     */
    const XML_PATH_PRODUCTS_ATTRIBUTES = 'synerise/product/attributes';
    const XML_PATH_PRODUCTS_CRON_ENABLED = 'synerise/product/cron_enabled';

    /**
     * CUSTOMERS SECTION
     */
    const XML_PATH_CUSTOMERS_ATTRIBUTES = 'synerise/customer/attributes';
    const XML_PATH_CUSTOMERS_CRON_ENABLED = 'synerise/customer/cron_enabled';

    /**
     * ORDERS SECTION
     */
    const XML_PATH_ORDERS_CRON_ENABLED = 'synerise/order/cron_enabled';

    /**
     * SYSTEM ONLY
     */
    const XML_PATH_CATALOG_ID = 'synerise/catalog/id';
    const XML_PATH_SUBSCRIBER_LAST_ID = 'synerise/subscriber/last_id';

    const MODELS = [
        'customer',
        'subscriber',
        'product',
        'order'
    ];

    public function __construct(
        DataInterfaceFactory $uiConfigFactory,
        Context $context
    ) {
        $this->uiConfigFactory = $uiConfigFactory;

        parent::__construct($context);
    }

    /**
     * @var DataInterfaceFactory
     */
    private $uiConfigFactory;
}
