<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Update\Client as ClientUpdate;

class CustomerSaveAfter  extends AbstractObserver implements ObserverInterface
{
    const EVENT = 'customer_save_after';

    const EXCLUDED_PATHS = [
        '/customer/account/createpost/',
        '/newsletter/manage/save/'
    ];


    /**
     * @var Http
     */
    protected $request;

    /**
     * @var ClientUpdate
     */
    protected $clientUpdate;


    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        Http $request,
        ClientUpdate $clientUpdate,
    ) {
        $this->request = $request;

        $this->clientUpdate = $clientUpdate;

        parent::__construct($scopeConfig, $logger);
    }

    public function execute(Observer $observer)
    {
        if (!$this->isLiveEventTrackingEnabled(self::EVENT)) {
            return;
        }

        if (in_array($this->request->getPathInfo(), self::EXCLUDED_PATHS)) {
            return;
        }

        try {
            $this->clientUpdate->sendCreateClientAndMarkAsSent($observer->getCustomer());
        } catch (\Exception $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e]);
        }
    }
}
