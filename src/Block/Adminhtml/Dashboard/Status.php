<?php
namespace Synerise\Integration\Block\Adminhtml\Dashboard;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Json\Helper\Data as JsonHelper;

class Status extends \Magento\Backend\Block\Template
{
    protected $collectionFactory;

    protected $header;

    protected $subHeader;

    protected $resendUrlPath;

    protected $resetStopIdUrlPath;

    protected $table;

    protected $connection;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null,
        ResourceConnection $resource
    ) {
        $this->connection = $resource->getConnection();

        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
    }

    public function setCollectionFactory($collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    public function getHeader()
    {
        return $this->header;
    }

    public function setStatusData($header, $subHeader, $resendUrlPath, $resetStopIdUrlPath, $table = null)
    {
        $this->header = $header;
        $this->subHeader = $subHeader;
        $this->resendUrlPath = $resendUrlPath;
        $this->resetStopIdUrlPath = $resetStopIdUrlPath;
        $this->table = $table;
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
        if($this->table) {
            $connection = $this->connection;
            $select = $connection->select()->from($connection->getTableName($this->table), 'COUNT(*)');
            return (int)$connection->fetchOne($select);
        } else {
            if (!$this->collectionFactory) {
                return '';
            }

            $collection = $this->collectionFactory->create()
                ->addAttributeToFilter('synerise_updated_at', array(
                    'gteq' => new \Zend_Db_Expr('updated_at')
                ));

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
        if(!$this->collectionFactory) {
            return '';
        }

        $collection = $this->collectionFactory->create();

        return $collection->getSize();
    }

    public function getResendUrl()
    {
        return $this->getUrl($this->resendUrlPath);
    }

    public function getResetIdUrl()
    {
        return $this->getUrl($this->resetStopIdUrlPath);
    }

}