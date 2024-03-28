<?php

namespace Synerise\Integration\Model\Config\Backend\Api;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Validator\Url;

class Host extends Value
{
    /**
     * @var Url
     */
    private $urlValidator;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param Url $urlValidator
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        Url $urlValidator,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->urlValidator = $urlValidator;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Validate a base URL field value
     *
     * @return void
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $value = rtrim($this->getValue(), DIRECTORY_SEPARATOR);
        $this->setValue($value);
        if (!$this->validateSecureUrl($value)) {
            throw new LocalizedException(__('Specify a valid secure URL.'));
        }
    }

    /**
     * Validate secure URL
     *
     * @param string $value
     * @return bool
     */
    private function validateSecureUrl($value): bool
    {
        return !preg_match('/\/$/', $value) && $this->urlValidator->isValid($value, ['https']);
    }
}
