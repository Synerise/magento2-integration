<?php
namespace Synerise\Integration\Block\Adminhtml\Synchronization\All;

use Magento\Backend\Block\Template\Context;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\Integration\Helper\Synchronization;

class Select extends \Magento\Backend\Block\Template
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Synchronization
     */
    protected $synchronization;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Synchronization $synchronization,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        $this->storeManager = $storeManager;
        $this->synchronization = $synchronization;

        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
    }

    /**
     * Get save url
     *
     * @return string
     */
    public function getSubmitUrl()
    {
        return $this->getUrl('*/*/all_schedule');
    }

    /**
     * @return array
     */
    public function getEnabledModels(): array
    {
        return $this->synchronization->getEnabledModels();
    }

    /**
     * @return array
     */
    public function getEnabledStores(): array
    {
        return $this->synchronization->getEnabledStores();
    }

    /**
     * @param int $storeId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreNameById(int $storeId)
    {
        return $this->storeManager->getStore($storeId)->getName();
    }
}