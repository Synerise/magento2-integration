<?php
namespace Synerise\Integration\Model;

use Ramsey\Uuid\Uuid;

class BusinessProfile extends \Magento\Framework\Model\AbstractModel
{
    const REQUIRED_PERMISSIONS = [
        "TRACKER_CREATE",
        "EVENTS",
        "CLIENT",
        "TRANSACTION"
    ];

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;
    
    private $validator;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Synerise\Integration\Model\BusinessProfile\Validator $validator, 
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [])
    {
        $this->encryptor = $encryptor;
        $this->validator = $validator;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init('Synerise\Integration\ResourceModel\BusinessProfile');
    }

    public function setApiKey($apiKey)
    {
        // don't save value, if an obscured value was received. This indicates that data was not changed.
        if (!preg_match('/^\*+$/', $apiKey) && !empty($apiKey)) {
            $this->setData('api_key', $this->encryptor->encrypt($apiKey));
            $uuid = (string) Uuid::uuid5(Uuid::NAMESPACE_OID, $apiKey);
            $this->setData('uuid', $uuid);
        }
    }

    public function getApiKey()
    {
        $value = $this->getData('api_key');
        if (!preg_match('/^\*+$/', $value) && !empty($value)) {
            return $this->encryptor->decrypt($value);
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    protected function _getValidationRulesBeforeSave()
    {
        return $this->validator;
    }
}