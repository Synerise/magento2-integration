<?php

namespace Synerise\Integration\Observer\Event\Cart;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\Api\DefaultApi;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\ClientaddedproducttocartRequest;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Api\Factory\DefaultApiFactory;
use Synerise\Integration\Helper\Api\Identity;
use Synerise\Integration\Helper\Api\Event\Cart;
use Synerise\Integration\Observer\AbstractObserver;

class RemoveProduct  extends AbstractObserver implements ObserverInterface
{
    const EVENT = 'sales_quote_remove_item';

    /**
     * @var Api
     */
    protected $apiHelper;

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
        DefaultApiFactory $defaultApiFactory,
        Api $apiHelper,
        Cart $cartHelper,
        Identity $identityHelper
    ) {
        $this->defaultApiFactory = $defaultApiFactory;
        $this->apiHelper = $apiHelper;
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

            $this->sendRemoveFromCartEvent(
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

    /**
     * @param ClientaddedproducttocartRequest $request
     * @param int|null $storeId
     * @return array
     * @throws ApiException
     * @throws ValidatorException
     */
    public function sendRemoveFromCartEvent(ClientaddedproducttocartRequest $request, ?int $storeId = null): array
    {
        return $this->getDefaultApiInstance($storeId)
            ->clientRemovedProductFromCartWithHttpInfo('4.4', $request);
    }

    /**
     * @param int|null $storeId
     * @return DefaultApi
     * @throws ValidatorException
     * @throws ApiException
     */
    public function getDefaultApiInstance(?int $storeId = null): DefaultApi
    {
        return $this->defaultApiFactory->get($this->apiHelper->getApiConfigByScope($storeId));
    }
}
