<?php

namespace Synerise\Integration\Model\Config\Backend\Api;

class Host extends \Magento\Framework\App\Config\Value
{
    /**
     * @var UrlValidator
     */
    private $urlValidator;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\Validator\Url $urlValidator
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Validator\Url $urlValidator,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->urlValidator = $urlValidator;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Validate a base URL field value
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave()
    {
        $value = rtrim($this->getValue(), DIRECTORY_SEPARATOR);
        $this->setValue($value);
        if(!$this->validateSecureUrl($value)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Specify a valid secure URL.'));
        }
    }

    /**
     * @param string $value
     * @return bool
     */
    private function validateSecureUrl($value)
    {
        return !preg_match('/\/$/', $value) && $this->urlValidator->isValid($value, ['https']);
    }
}
