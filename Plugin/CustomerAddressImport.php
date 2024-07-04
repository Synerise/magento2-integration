<?php

namespace Synerise\Integration\Plugin;

use Magento\CustomerImportExport\Model\Import\AbstractCustomer;
use Magento\CustomerImportExport\Model\Import\Address;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\MessageQueue\Publisher\Data\Batch as BatchPublisher;
use Synerise\Integration\Model\Synchronization\Config;
use Synerise\Integration\Model\Tracking\ConfigFactory;
use Synerise\Integration\SyneriseApi\Sender\Data\Customer as CustomerSender;

class CustomerAddressImport
{
    public const EVENT = 'customer_import_interceptor';

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var Config
     */
    protected $synchronization;

    /**
     * @var BatchPublisher
     */
    protected $batchPublisher;

    /**
     * @var bool
     */
    protected $importStarted = false;

    /**
     * @var array
     */
    protected $customersToUpdate = [];

    public function __construct(
        Logger $loggerHelper,
        ConfigFactory $configFactory,
        Config $synchronization,
        BatchPublisher $batchPublisher
    ) {
        $this->loggerHelper = $loggerHelper;
        $this->configFactory = $configFactory;
        $this->synchronization = $synchronization;
        $this->batchPublisher = $batchPublisher;
    }

    /**
     * Collect data of customer
     *
     * @param Address $subject
     * @param bool $result
     * @param array $rowData
     * @param int $rowNum
     * @return bool
     */
    public function afterValidateRow(Address $subject, bool $result, array $rowData, $rowNum)
    {
        if (!$result || !$this->importStarted) {
            return $result;
        }

        try {
            $email = $rowData[AbstractCustomer::COLUMN_EMAIL];
            $websiteId = $subject->getWebsiteId($rowData[AbstractCustomer::COLUMN_WEBSITE]);

            $customerId = $subject->getCustomerStorage()->getCustomerId($email, $websiteId);
            $storeId = $subject->getCustomerStorage()->getCustomerStoreId($email, $websiteId);

            if ($storeId && $this->synchronization->isStoreConfigured($storeId)) {
                $this->customersToUpdate[$storeId][$customerId] = $email;
            }
        } catch (\Exception $e) {
            $this->loggerHelper->error($e);
        }

        return $result;
    }

    /**
     * Mark import started
     *
     * @param Address $subject
     * @return void
     */
    public function beforeImportData(Address $subject)
    {
        if ($this->synchronization->isModelEnabled(CustomerSender::MODEL)) {
            $this->importStarted = true;
        }
    }

    /**
     * Process collected data after import
     *
     * @param Address $subject
     * @return void
     */
    public function afterImportData(Address $subject)
    {
        foreach ($this->customersToUpdate as $storeId => $customers) {
            $this->handleUpdate(array_keys($customers), $storeId);
        }
    }

    /**
     * Handle customer creates or updates
     *
     * @param array $ids
     * @param int $storeId
     * @return void
     */
    protected function handleUpdate(array $ids, int $storeId)
    {
        try {
            if (!$this->configFactory->get($storeId)->isEventTrackingEnabled(self::EVENT)) {
                return;
            }

            $this->batchPublisher->publish(
                CustomerSender::MODEL,
                $ids,
                $storeId
            );
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->loggerHelper->error($e);
            }
        }
    }
}
