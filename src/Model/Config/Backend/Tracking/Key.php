<?php

namespace Synerise\Integration\Model\Config\Backend\Tracking;

class Key extends \Magento\Framework\App\Config\Value
{
    /**
     * @var \Synerise\Integration\Helper\Tracking
     */
    var $trackingHelper;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param \Synerise\Integration\Helper\Tracking
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \Synerise\Integration\Helper\Tracking $trackingHelper,
        array $data = []
    ) {
        $this->trackingHelper = $trackingHelper;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function beforeSave()
    {
        $value = trim($this->getValue());

        if ($value != '' && !$this->trackingHelper->validateKeyFormat($value)) {
            throw new \Magento\Framework\Exception\ValidatorException(__('Invalid tracking key format'));
        }

        $this->setValue($value);

        parent::beforeSave();
    }
}
