<?php

namespace Synerise\Integration\Observer\Event;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\Helper\Tracking\State;
use Synerise\Integration\MessageQueue\Publisher\Event as EventPublisher;
use Synerise\Integration\Model\Tracking\ConfigFactory;
use Synerise\Integration\SyneriseApi\Mapper\Event\CartAddRemove;
use Synerise\Integration\SyneriseApi\Sender\Event as EventSender;

class CartAddProduct implements ObserverInterface
{
    public const EVENT = 'checkout_cart_add_product_complete';

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var Cookie
     */
    protected $cookieHelper;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var State
     */
    protected $stateHelper;

    /**
     * @var CartAddRemove
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
     * @param CartAddRemove $mapper
     * @param ConfigFactory $configFactory
     * @param Cookie $cookieHelper
     * @param Logger $loggerHelper
     * @param State $stateHelper
     * @param EventPublisher $publisher
     * @param EventSender $sender
     */
    public function __construct(
        CartAddRemove $mapper,
        ConfigFactory $configFactory,
        Cookie $cookieHelper,
        Logger $loggerHelper,
        State $stateHelper,
        EventPublisher $publisher,
        EventSender $sender
    ) {
        $this->mapper = $mapper;
        $this->configFactory = $configFactory;
        $this->cookieHelper = $cookieHelper;
        $this->loggerHelper = $loggerHelper;
        $this->stateHelper = $stateHelper;
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
        if ($this->stateHelper->isAdmin()) {
            return;
        }

        try {
            /** @var Quote\Item $quoteItem */
            $quoteItem = $observer->getQuoteItem();
            $storeId = $quoteItem->getStoreId();

            $config = $this->configFactory->create($storeId);
            if (!$config->isEventTrackingEnabled(self::EVENT)) {
                return;
            }

            if ($quoteItem->getProduct()->getParentProductId()) {
                return;
            }

            $uuid = $this->cookieHelper->getSnrsUuid();
            if (!$uuid && !$quoteItem->getQuote()->getCustomerEmail()) {
                return;
            }

            $eventClientAction = $this->mapper->prepareRequest(
                self::EVENT,
                $quoteItem,
                $uuid,
                $this->cookieHelper->shouldIncludeSnrsParams($storeId) ? $this->cookieHelper->getSnrsParams() : []
            );

            if ($config->isEventMessageQueueEnabled(self::EVENT)) {
                $this->publisher->publish(self::EVENT, $eventClientAction, $storeId);
            } else {
                $this->sender->send(self::EVENT, $eventClientAction, $storeId);
            }
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->loggerHelper->error($e);
            }
        }
    }
}
