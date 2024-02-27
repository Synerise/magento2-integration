<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CustomeventRequestParams;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\MessageQueue\Publisher\Event as EventPublisher;
use Synerise\Integration\SyneriseApi\Mapper\CartStatus as Mapper;
use Synerise\Integration\SyneriseApi\Sender\Event as EventSender;

class CartStatus implements ObserverInterface
{
    public const EVENT = 'sales_quote_save_after';

    /**
     * @var CustomeventRequestParams
     */
    protected $previousParams = null;

    /**
     * @var Cookie
     */
    protected $cookieHelper;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var Mapper
     */
    protected $mapper;

    /**
     * @var EventPublisher
     */
    protected $publisher;

    /**
     * @var EventSender
     */
    protected $sender;

    /**
     * @param Cookie $cookieHelper
     * @param Logger $loggerHelper
     * @param Tracking $trackingHelper
     * @param Mapper $mapper
     * @param EventPublisher $publisher
     * @param EventSender $sender
     */
    public function __construct(
        Cookie $cookieHelper,
        Logger $loggerHelper,
        Tracking $trackingHelper,
        Mapper $mapper,
        EventPublisher $publisher,
        EventSender $sender
    ) {
        $this->cookieHelper = $cookieHelper;
        $this->loggerHelper = $loggerHelper;
        $this->trackingHelper = $trackingHelper;
        $this->mapper = $mapper;
        $this->publisher = $publisher;
        $this->sender = $sender;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->trackingHelper->getContext()->isAdminStore()) {
            return;
        }

        try {
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $observer->getQuote();
            $storeId = $quote->getStoreId();

            if (!$this->trackingHelper->isEventTrackingAvailable(self::EVENT, $storeId)) {
                return;
            }

            $uuid = $this->trackingHelper->getClientUuid();
            if (!$uuid && !$quote->getCustomerEmail()) {
                return;
            }

            $cartStatusEvent = $this->mapper->prepareRequest(
                $quote,
                $uuid,
                $this->cookieHelper->shouldIncludeSnrsParams() ? $this->cookieHelper->getSnrsParams() : [],
                $quote->dataHasChangedFor('reserved_order_id')
            );

            if ($this->previousParams && $this->previousParams === $cartStatusEvent->getParams()) {
                return;
            }

            if ($this->trackingHelper->isEventMessageQueueAvailable(self::EVENT, $storeId)) {
                $this->publisher->publish(self::EVENT, $cartStatusEvent, $storeId);
            } else {
                $this->sender->send(self::EVENT, $cartStatusEvent, $storeId);
            }
            $this->previousParams = $cartStatusEvent->getParams();
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->loggerHelper->error($e);
            }
        }
    }
}
