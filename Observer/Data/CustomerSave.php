<?php

namespace Synerise\Integration\Observer\Data;

use Magento\Customer\Model\Customer;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\MessageQueue\Publisher\Data\Item as DataItemPublisher;
use Synerise\Integration\Model\Synchronization\Config;
use Synerise\Integration\Model\Tracking\ConfigFactory;
use Synerise\Integration\SyneriseApi\Sender\Data\Customer as Sender;

class CustomerSave implements ObserverInterface
{
    public const EVENT = 'customer_save_after';

    public const EXCLUDED_PATHS = [
        '/newsletter/manage/save/'
    ];

    /**
     * @var Config
     */
    protected $synchronization;

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var Http
     */
    private $request;

    /**
     * @var Sender
     */
    protected $sender;

    /**
     * @var DataItemPublisher
     */
    protected $dataItemPublisher;

    /**
     * @param Config $synchronization
     * @param ConfigFactory $configFactory
     * @param Logger $loggerHelper
     * @param Http $request
     * @param DataItemPublisher $dataItemPublisher
     * @param Sender $sender
     */
    public function __construct(
        Config $synchronization,
        ConfigFactory $configFactory,
        Logger $loggerHelper,
        Http $request,
        DataItemPublisher $dataItemPublisher,
        Sender $sender
    ) {
        $this->synchronization = $synchronization;
        $this->configFactory = $configFactory;
        $this->loggerHelper = $loggerHelper;
        $this->request = $request;
        $this->dataItemPublisher = $dataItemPublisher;
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
        if (in_array($this->request->getPathInfo(), self::EXCLUDED_PATHS)) {
            return;
        }

        if (!$this->synchronization->isModelEnabled(Sender::MODEL)) {
            return;
        }

        try {
            /** @var Customer $customer */
            $customer = $observer->getCustomer();
            $storeId = $customer->getStoreId();

            $config = $this->configFactory->create($storeId);
            if (!$config->isEventTrackingEnabled(self::EVENT)) {
                return;
            }

            if ($config->isEventMessageQueueEnabled(self::EVENT)) {
                $this->dataItemPublisher->publish(
                    Sender::MODEL,
                    (int) $customer->getEntityId(),
                    $storeId
                );
            } else {
                $this->sender->sendItems([$customer], $storeId);
            }
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->loggerHelper->error($e);
            }
        }
    }
}
