<?php
namespace Synerise\Integration\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Template\Context;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Json\Helper\Data as JsonHelper;

class Status extends \Magento\Backend\Block\Template
{
    protected $collectionFactory;

    protected $header;

    protected $subHeader;

    protected $scheduleAllUrlPath;

    protected $table;

    protected $column;

    protected $connection;

    /**
     * @param Context $context
     * @param ResourceConnection $resource
     * @param ProductMetadataInterface $productMetadata
     * @param array $data
     * @param JsonHelper|null $jsonHelper
     * @param DirectoryHelper|null $directoryHelper
     */
    public function __construct(
        Context $context,
        ResourceConnection $resource,
        ProductMetadataInterface $productMetadata,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        $this->connection = $resource->getConnection();

        if (version_compare($productMetadata->getVersion(), '2.4', 'lt')) {
            parent::__construct($context, $data);
        } else {
            parent::__construct($context, $data, $jsonHelper, $directoryHelper);
        }
    }

    public function setCollectionFactory($collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    public function getHeader()
    {
        return $this->header;
    }

    public function setStatusData($header, $subHeader, $scheduleAllUrlPath, $table = null, $column= 'COUNT(*)')
    {
        $this->header = $header;
        $this->subHeader = $subHeader;
        $this->scheduleAllUrlPath = $scheduleAllUrlPath;
        $this->table = $table;
        $this->column = $column;
    }

    public function getSubHeader()
    {
        return $this->subHeader;
    }

    /**
     * Number of items sent to Synerise
     *
     * @return int
     */
    public function getProcessedItemsCount()
    {
        if ($this->table) {
            $connection = $this->connection;
            $select = $connection->select()->from($connection->getTableName($this->table), $this->column);
            return (int) $connection->fetchOne($select);
        } else {
            if (!$this->collectionFactory) {
                return 0;
            }

            $collection = $this->collectionFactory->create()
                ->addAttributeToFilter('synerise_updated_at', [
                    'gteq' => new \Zend_Db_Expr('updated_at')
                ]);

            return $collection->getSize();
        }
    }

    /**
     * Total number of items
     *
     * @return int
     */
    public function getTotalItemsCount()
    {
        if (!$this->collectionFactory) {
            return 0;
        }

        $collection = $this->collectionFactory->create();

        return $collection->getSize();
    }

    public function getScheduleAllUrl()
    {
        return $this->getUrl('synerise/synchronization/all_select');
    }
}
