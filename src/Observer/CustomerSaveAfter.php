<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Identity;
use Synerise\Integration\Helper\Update\Client;

class CustomerSaveAfter  extends AbstractObserver implements ObserverInterface
{
    const EVENT = 'customer_save_after';

    const EXCLUDED_PATHS = [
        '/newsletter/manage/save/'
    ];

    protected $isSent = false;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var Client
     */
    protected $clientUpdate;


    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        Http $request,
        Client $clientUpdate
    ) {
        $this->request = $request;

        $this->clientUpdate = $clientUpdate;

        parent::__construct($scopeConfig, $logger);
    }

    public function execute(Observer $observer)
    {
        if (!$this->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        if ($this->isSent || in_array($this->request->getPathInfo(), self::EXCLUDED_PATHS)) {
            return;
        }

        try {
            $customer = $observer->getCustomer();

            list ($body, $statusCode, $headers) = $this->clientUpdate->sendCreateClient(
                $this->clientUpdate->prepareCreateClientRequest(
                    $observer->getCustomer(),
                    Identity::generateUuidByEmail($customer->getEmail()),
                    $customer->getStoreId()
                ),
                $customer->getStoreId()
            );

            if ($statusCode == 202) {
                $this->markAsSent($customer);
            } else {
                $this->logger->error(
                    'Client update - invalid status',
                    [
                        'status' => $statusCode,
                        'body' => $body
                    ]
                );
            }
        } catch (\Exception $e) {
            $this->logger->error('Client update failed', ['exception' => $e]);
        }
    }

    protected function markAsSent($customer)
    {
        $this->isSent = true;
        $this->clientUpdate->markAsSent([$customer->getId()], $customer->getStoreId());
    }
}
