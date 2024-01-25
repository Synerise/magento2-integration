<?php
namespace Synerise\Integration\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use Ramsey\Uuid\Uuid;
use Synerise\Integration\Model\Workspace\Validator;
use Synerise\Integration\SyneriseApi\ConfigFactory;

class Workspace extends AbstractModel
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
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param EncryptorInterface $encryptor
     * @param Validator $validator
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        EncryptorInterface $encryptor,
        Validator $validator,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->encryptor = $encryptor;
        $this->validator = $validator;
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;

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

    /**
     * Save linked config
     *
     * @return Workspace
     */
    public function afterSave()
    {
        $workspaceMapString = $this->scopeConfig->getValue(self::XML_PATH_WORKSPACE_MAP);
        if ($workspaceMapString) {
            $workspaceMap = json_decode($workspaceMapString);
            foreach ($workspaceMap as $websiteId => $workspaceId) {
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
