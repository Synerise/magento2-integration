<?php

namespace Synerise\Integration\Observer\Event;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\Helper\Tracking\State;
use Synerise\Integration\Model\Tracking\ConfigFactory;
use Synerise\Integration\SyneriseApi\Mapper\Event\CartStatus as Mapper;
use Synerise\Integration\MessageQueue\Publisher\Event as EventPublisher;
use Synerise\Integration\SyneriseApi\Sender\Event as EventSender;

class CartQtyUpdate implements ObserverInterface
{
    public const EVENT = 'checkout_cart_update_items_after';

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
     * @param ConfigFactory $configFactory
     * @param Cookie $cookieHelper
     * @param Logger $loggerHelper
     * @param State $stateHelper
     * @param Mapper $mapper
     * @param EventPublisher $publisher
     * @param EventSender $sender
     */
    public function __construct(
        ConfigFactory $configFactory,
        Cookie $cookieHelper,
        Logger $loggerHelper,
        State $stateHelper,
        Mapper $mapper,
        EventPublisher $publisher,
        EventSender $sender
    ) {
        $this->configFactory = $configFactory;
        $this->cookieHelper = $cookieHelper;
        $this->loggerHelper = $loggerHelper;
        $this->stateHelper = $stateHelper;
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
        if ($this->stateHelper->isAdminStore()) {
            return;
        }

        try {
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $observer->getCart()->getQuote();
            $storeId = $quote->getStoreId();

            $config = $this->configFactory->create($storeId);
            if (!$config->isEventTrackingEnabled(self::EVENT)) {
                return;
            }

            $uuid = $this->cookieHelper->getSnrsUuid();
            if (!$uuid && !$quote->getCustomerEmail()) {
                return;
            }

            $quote->collectTotals();

            if (!$this->hasItemDataChanges($quote)) {
                // quote save won't be triggered, send event.
                $cartStatusEvent = $this->mapper->prepareRequest(
                    $quote,
                    $uuid,
                    $this->cookieHelper->shouldIncludeSnrsParams() ? $this->cookieHelper->getSnrsParams() : []
                );

                if ($config->isEventMessageQueueEnabled(self::EVENT)) {
                    $this->publisher->publish(self::EVENT, $cartStatusEvent, $storeId);
                } else {
                    $this->sender->send(self::EVENT, $cartStatusEvent, $storeId);
                }
            }
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->loggerHelper->error($e);
            }
        }
    }

    /**
     * Check if cart was actually updated
     *
     * @param Quote $quote
     * @return bool
     */
    public function hasItemDataChanges(Quote $quote): bool
    {
        return ($quote->dataHasChangedFor('subtotal') || $quote->dataHasChangedFor('items_qty'));
    }
}
