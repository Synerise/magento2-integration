<?php
namespace Synerise\Integration\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\ScopeInterface;
use Ramsey\Uuid\Uuid;
use Synerise\Integration\SyneriseApi\ConfigFactory;

class Workspace extends \Magento\Framework\Model\AbstractModel
{
    const XML_PATH_WORKSPACE_MAP = 'synerise/workspace/map';

    const REQUIRED_PERMISSIONS = [
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
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var Workspace\Validator
     */
    protected $validator;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Synerise\Integration\Model\Workspace\Validator $validator,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [])
    {
        $this->encryptor = $encryptor;
        $this->validator = $validator;
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init('Synerise\Integration\Model\ResourceModel\Workspace');
    }

    public function setApiKey($apiKey)
    {
        // don't save value, if an obscured value was received. This indicates that data was not changed.
        if (!empty($apiKey) && !preg_match('/^\*+$/', $apiKey)) {
            $this->setData('api_key', $this->encryptor->encrypt($apiKey));
            $uuid = (string) Uuid::uuid5(Uuid::NAMESPACE_OID, $apiKey);
            $this->setData('uuid', $uuid);
        }
    }

    public function getApiKey()
    {
        $value = $this->getData('api_key');
        if (!empty($value) && !preg_match('/^\*+$/', $value)) {
            return $this->encryptor->decrypt($value);
        }
        return null;
    }

    public function setGuid($guid)
    {
        $this->setData('guid', !empty($guid) ? $this->encryptor->encrypt($guid) : null);
    }

    public function getGuid()
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
    public function afterSave()
    {
        $workspaceMapString = $this->scopeConfig->getValue(self::XML_PATH_WORKSPACE_MAP);
        if ($workspaceMapString) {
            $workspaceMap = json_decode($workspaceMapString);
            foreach($workspaceMap as $websiteId => $workspaceId) {
                if ($this->getId() == $workspaceId) {
                    $this->configWriter->save(
                        ConfigFactory::XML_PATH_API_KEY,
                        $this->getApiKey(),
                        ScopeInterface::SCOPE_WEBSITES,
                        $websiteId
                    );

                    $this->configWriter->save(
                        ConfigFactory::XML_PATH_API_KEY,
                        $this->getData('api_key'),
                        ScopeInterface::SCOPE_WEBSITES,
                        $websiteId
                    );

                    $guid = $this->getGuid();
                    if ($guid) {
                        $this->configWriter->save(
                            \Synerise\Integration\Model\Config\Backend\Workspace::XML_PATH_API_BASIC_TOKEN,
                            $this->encryptor->encrypt(base64_encode("{$guid}:{$this->getApiKey()}")),
                            ScopeInterface::SCOPE_WEBSITES,
                            $websiteId
                        );
                    } else {
                        $this->configWriter->delete(
                            \Synerise\Integration\Model\Config\Backend\Workspace::XML_PATH_API_BASIC_TOKEN,
                            ScopeInterface::SCOPE_WEBSITES,
                            $websiteId
                        );
                    }
                }
            }

        }

        return parent::afterSave();
    }
}