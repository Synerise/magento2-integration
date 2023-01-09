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

class CartStatus  extends AbstractObserver implements ObserverInterface
{
    const EVENT = 'sales_quote_save_after';

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
            /** @var Quote $quote */
            $quote = $observer->getQuote();
            $quote->collectTotals();

            if ($this->cartHelper->hasItemDataChanges($quote)) {
                $this->cartHelper->sendCartStatusEvent(
                    $this->cartHelper->prepareCartStatusRequest(
                        $quote,
                        $this->identityHelper->getClientUuid()
                    )
                );
            }
        } catch (Exception $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e]);
        }
    }
}
