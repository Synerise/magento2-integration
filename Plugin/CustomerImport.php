<?php

namespace Synerise\Integration\Plugin;

use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\CustomerImportExport\Model\Import\Customer;
use Magento\Framework\DB\Select;
use Magento\ImportExport\Model\Import;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\MessageQueue\Publisher\Data\Batch as BatchPublisher;
use Synerise\Integration\MessageQueue\Publisher\Event as EventPublisher;
use Synerise\Integration\Model\Config\Source\CustomerDeleteBehavior;
use Synerise\Integration\Model\Synchronization\Config;
use Synerise\Integration\Model\Tracking\ConfigFactory;
use Synerise\Integration\Observer\Data\CustomerDelete;
use Synerise\Integration\SyneriseApi\Mapper\Event\CustomerDelete as EventMapper;
use Synerise\Integration\SyneriseApi\Sender\Data\Customer as CustomerSender;
use Synerise\Integration\SyneriseApi\Sender\Event as EventSender;

class CustomerImport
{
    public const EVENT = 'customer_import_interceptor';

    /**
     * @var CustomerCollectionFactory
     */
    protected $customerCollectionFactory;

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
     * @var CustomerSender
     */
    protected $customerSender;

    /**
     * @var EventMapper
     */
    protected $eventMapper;

    /**
     * @var EventPublisher
     */
    protected $eventPublisher;

    /**
     * @var EventSender
     */
    protected $eventSender;

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
    protected $customersToDelete = [];

    /**
     * @var array
     */
    protected $customersToUpdate = [];

    /**
     * @var array
     */
    protected $customersToLoad = [];

    public function __construct(
        CustomerCollectionFactory $collectionFactory,
        Logger $loggerHelper,
        ConfigFactory $configFactory,
        Config $synchronization,
        CustomerSender $customerSender,
        EventMapper $eventMapper,
        EventPublisher $eventPublisher,
        EventSender $eventSender,
        BatchPublisher $batchPublisher
    ) {
        $this->customerCollectionFactory = $collectionFactory;
        $this->loggerHelper = $loggerHelper;
        $this->configFactory = $configFactory;
        $this->synchronization = $synchronization;
        $this->customerSender = $customerSender;
        $this->eventMapper = $eventMapper;
        $this->eventPublisher = $eventPublisher;
        $this->eventSender = $eventSender;
        $this->batchPublisher = $batchPublisher;
    }

    /**
     * Collect data of customer
     *
     * @param Customer $subject
     * @param bool $result
     * @param array $rowData
     * @param int $rowNum
     * @return bool
     */
    public function afterValidateRow(Customer $subject, bool $result, array $rowData, $rowNum)
    {
        if (!$result || !$this->importStarted) {
            return $result;
        }

        try {
            $email = $rowData[Customer::COLUMN_EMAIL];
            $websiteId = $subject->getWebsiteId($rowData[Customer::COLUMN_WEBSITE]);

            $customerId = $subject->getCustomerStorage()->getCustomerId($email, $websiteId);
            $storeId = $subject->getCustomerStorage()->getCustomerStoreId($email, $websiteId);

            if ($subject->getBehavior($rowData) == Import::BEHAVIOR_DELETE) {
                if ($storeId && $customerId && $this->synchronization->isStoreConfigured($storeId)) {
                    $this->customersToDelete[$storeId][$customerId] = $email;
                }
            } else {
                if ($storeId && $this->synchronization->isStoreConfigured($storeId)) {
                    $this->customersToUpdate[$storeId][$customerId] = $email;
                } else {
                    $uniqueKey = $email . '_' . $websiteId;
                    $this->customersToLoad[$uniqueKey] = [
                        'email' => $email,
                        'website_id' => $websiteId,
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->loggerHelper->error($e);
        }

        return $result;
    }

    /**
     * Mark import started
     *
     * @param Customer $subject
     * @return void
     */
    public function beforeImportData(Customer $subject)
    {
        if ($this->synchronization->isModelEnabled(CustomerSender::MODEL)) {
            $this->importStarted = true;
        }
    }

    /**
     * Process collected data after import
     *
     * @param Customer $subject
     * @return void
     */
    public function afterImportData(Customer $subject)
    {
        foreach ($this->customersToDelete as $storeId => $customers) {
            $this->handleDelete($customers, $storeId);
        }

        if ($this->customersToLoad) {
            $this->loadCustomersData($this->customersToLoad);
        }

        foreach ($this->customersToUpdate as $storeId => $customers) {
            $this->handleUpdate(array_keys($customers), $storeId);
        }
    }

    /**
     * Handle customer deletions
     *
     * @param array $customers
     * @param int $storeId
     * @return void
     */
    protected function handleDelete(array $customers, int $storeId)
    {
        try {
            $config = $this->configFactory->get($storeId);
            if (!$this->configFactory->get($storeId)->isEventTrackingEnabled(self::EVENT)) {
                return;
            }

            $queueEnabled = $config->isEventMessageQueueEnabled(self::EVENT);

            if ($config->getCustomerDeleteBehavior() == CustomerDeleteBehavior::REMOVE) {
                foreach ($customers as $customerId => $email) {
                    $this->handleDeleteRequest($customerId, $storeId, $queueEnabled);
                }
            } elseif ($config->getCustomerDeleteBehavior() == CustomerDeleteBehavior::SEND_EVENT) {
                foreach ($customers as $email) {
                    $this->handleDeleteEvent($email, $storeId, $queueEnabled);
                }
            }
        } catch (\Exception $e) {
            $this->loggerHelper->error($e);
        }
    }

    /**
     * Handle customer delete as event
     *
     * @param string $email
     * @param int $storeId
     * @param bool $queueEnabled
     * @return void
     */
    protected function handleDeleteEvent(string $email, int $storeId, bool $queueEnabled)
    {
        try {
            $clientDeleteAccount = $this->eventMapper->prepareRequest($email);
            if ($queueEnabled) {
                $this->eventPublisher->publish(CustomerDelete::EVENT, $clientDeleteAccount, $storeId);
            } else {
                $this->eventSender->send(CustomerDelete::EVENT, $clientDeleteAccount, $storeId);
            }
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->loggerHelper->error($e);
            }
        }
    }

    /**
     * Handle customer delete as request
     *
     * @param int $customerId
     * @param int $storeId
     * @param bool $queueEnabled
     * @return void
     */
    protected function handleDeleteRequest(int $customerId, int $storeId, bool $queueEnabled)
    {
        try {
            if ($queueEnabled) {
                $this->eventPublisher->publish(CustomerDelete::REQUEST, $customerId, $storeId, $customerId);
            } else {
                $this->customerSender->deleteItem($customerId, $storeId, $customerId);
            }
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->loggerHelper->error($e);
            }
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

    /**
     * Load customer's data that can be found by given identifiers.
     *
     * @param array $customerIdentifiers With keys "email" and "website_id".
     * @return void
     */
    protected function loadCustomersData(array $customerIdentifiers): void
    {
        try {
            $collection = $this->customerCollectionFactory->create();
            $collection->removeAttributeToSelect();
            $select = $collection->getSelect();
            $customerTableId = array_keys($select->getPart(Select::FROM))[0];

            $pageSize = 10000;
            $getChuck = function (int $offset) use ($customerIdentifiers, $pageSize) {
                return array_slice($customerIdentifiers, $offset, $pageSize);
            };
            $offset = 0;
            for ($chunk = $getChuck($offset); !empty($chunk); $offset += $pageSize, $chunk = $getChuck($offset)) {
                $emails = array_column($chunk, 'email');
                $chunkSelect = clone $select;
                $chunkSelect->where($customerTableId . '.email IN (?)', $emails);
                $customers = $collection->getConnection()->fetchAll($chunkSelect);
                foreach ($customers as $customer) {
                    if ($customer['store_id'] && $this->synchronization->isStoreConfigured($customer['store_id'])) {
                        $this->addCustomerByArray($customer);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->loggerHelper->error($e);
        }
    }

    /**
     * Add a customer by an array
     *
     * @param array $customer
     * @return void
     */
    public function addCustomerByArray(array $customer): void
    {
        $email = isset($customer['email']) ? mb_strtolower(trim($customer['email'])) : '';
        $customerId = (int) $customer['entity_id'];
        $storeId = $customer['store_id'] ?? null;

        if ($storeId && $customerId) {
            $this->customersToUpdate[$storeId][$customerId] = $email;
        }
    }
}
