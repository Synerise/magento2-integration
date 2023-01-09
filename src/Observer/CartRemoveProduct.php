<?php

namespace Synerise\Integration\Observer;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Identity;
use Synerise\Integration\Helper\Event\Cart;

class CartRemoveProduct  extends AbstractObserver implements ObserverInterface
{
    const EVENT = 'sales_quote_remove_item';

    /**
     * @var Cart
     */
    protected $cartHelper;

    /**
     * @var Identity
     */
    protected $identityHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        Cart $cartHelper,
        Identity $identityHelper
    ) {
        $this->cartHelper = $cartHelper;
        $this->identityHelper = $identityHelper;

        parent::__construct($scopeConfig, $logger);
    }

    public function execute(Observer $observer)
    {
        if (!$this->isLiveEventTrackingEnabled(self::EVENT)) {
            return;
        }

        if ($this->identityHelper->isAdminStore()) {
            return;
        }

        try {
            /** @var Quote\Item $quoteItem */
            $quoteItem = $observer->getQuoteItem();
            if ($quoteItem->getProduct()->getParentProductId()) {
                return;
            }

            $this->cartHelper->sendRemoveFromCartEvent(
                $this->cartHelper->prepareAddToCartRequest(
                    $quoteItem,
                    self::EVENT,
                    $this->identityHelper->getClientUuid()
                )
            );
        } catch (Exception $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e]);
        }
    }
}
