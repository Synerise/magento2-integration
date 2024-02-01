<?php
namespace Synerise\Integration\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Ramsey\Uuid\Uuid;
use Synerise\Integration\Model\Workspace\Validator;

class Workspace extends AbstractModel implements WorkspaceInterface
{
    public const XML_PATH_WORKSPACE_MAP = 'synerise/workspace/map';

    public const REQUIRED_PERMISSIONS = [
        "API_CLIENT_CREATE",
        "API_BATCH_CLIENT_CREATE",
        "API_BATCH_TRANSACTION_CREATE",
        "API_TRANSACTION_CREATE",
        "API_CUSTOM_EVENTS_CREATE",
        "API_ADDED_TO_CART_EVENTS_CREATE",
        "API_REMOVED_FROM_CART_EVENTS_CREATE",
        "API_ADDED_TO_FAVORITES_EVENTS_CREATE",
        "API_LOGGED_IN_EVENTS_CREATE",
        "API_LOGGED_OUT_EVENTS_CREATE",
        "API_REGISTERED_EVENTS_CREATE",
        "CATALOGS_CATALOG_CREATE",
        "CATALOGS_CATALOG_READ",
        "CATALOGS_ITEM_BATCH_CATALOG_CREATE",
        "TRACKER_CREATE"
    ];

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'synerise_workspace';

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param EncryptorInterface $encryptor
     * @param Validator $validator
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        EncryptorInterface $encryptor,
        Validator $validator,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->encryptor = $encryptor;
        $this->validator = $validator;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Workspace
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Synerise\Integration\Model\ResourceModel\Workspace::class);
    }

    /**
     * Set API key
     *
     * @param string $apiKey
     * @return void
     */
    public function setApiKey(string $apiKey)
    {
        // don't save value, if an obscured value was received. This indicates that data was not changed.
        if (!empty($apiKey) && !preg_match('/^\*+$/', $apiKey)) {
            $this->setData('api_key', $this->encryptor->encrypt($apiKey));
            $uuid = (string) Uuid::uuid5(Uuid::NAMESPACE_OID, $apiKey);
            $this->setData('uuid', $uuid);
        }
    }

    /**
     * Check if API key is set
     *
     * @return bool
     */
    public function isApiKeySet()
    {
        $apiKey = $this->getApiKey();
        return $apiKey !== null && trim($apiKey) !== '';
    }

    /**
     * Get API key
     *
     * @return string|null
     */
    public function getApiKey()
    {
        $value = $this->getData('api_key');
        if (!empty($value) && !preg_match('/^\*+$/', $value)) {
            return $this->encryptor->decrypt($value);
        }
        return null;
    }

    /**
     * Set GUID
     *
     * @param string|null $guid
     * @return void
     */
    public function setGuid(?string $guid)
    {
        $this->setData('guid', !empty($guid) ? $this->encryptor->encrypt($guid) : null);
    }

    /**
     * Get GUID
     *
     * @return string|null
     */
    public function getGuid(): ?string
    {
        $value = $this->getData('guid');
        if (!empty($value) && !preg_match('/^\*+$/', $value)) {
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
