<?php
namespace Synerise\Integration\Block\Adminhtml\Log;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Synerise\Integration\Helper\Log;

class Index extends Template
{
    /**
     * @var Log
     */
    protected $logHelper;

    /**
     * @param Context $context
     * @param Log $logHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Log $logHelper,
        array $data = []
    )
    {
        $this->logHelper = $logHelper;
        parent::__construct($context, $data);
    }
    
    public function getLogFiles()
    {
        return $this->logHelper->buildLogData();
    }

    public function downloadLogFiles($fileName)
    {
        return $this->getUrl('synerise/log/download', ['filename' => $fileName]);
    }

    public function deleteLogFile($fileName)
    {
        return $this->getUrl('synerise/log/delete',['filename' => $fileName]);
    }
}