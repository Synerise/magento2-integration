<?php

namespace Synerise\Integration\Model\Config\Backend\Api;

use Magento\Store\Api\StoreWebsiteRelationInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManager;
use Synerise\Integration\ResourceModel\Cron\Status;

class Key extends \Magento\Config\Model\Config\Backend\Encrypted
{
    /**
     * @var \Zend\Validator\Uuid
     */
    protected $uuidValidator;

    /**
     * @var Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Synerise\Integration\Helper\Api
     */
    private $apiHelper;

    /**
     * @var StoreWebsiteRelationInterface
     */
    private StoreWebsiteRelationInterface $storeWebsiteRelation;

    /**
     * @var Status
     */
    private Status $statusResourceModel;

    /**
     * @var StoreManager
     */
    private StoreManager $storeManager;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param \Synerise\Integration\Helper\Api
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \Psr\Log\LoggerInterface $logger,
        \Zend\Validator\Uuid $uuidValidator,
        \Synerise\Integration\Helper\Api $apiHelper,
        StoreManager $storeManager,
        StoreWebsiteRelationInterface $storeWebsiteRelation,
        Status $statusResourceModel,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->storeWebsiteRelation = $storeWebsiteRelation;
        $this->logger = $logger;
        $this->uuidValidator = $uuidValidator;
        $this->apiHelper = $apiHelper;
        $this->statusResourceModel = $statusResourceModel;

        parent::__construct($context, $registry, $config, $cacheTypeList, $encryptor, $resource, $resourceCollection, $data);
    }

    public function beforeSave()
    {
        $value = trim($this->getValue());

        // don't save value, if an obscured value was received. This indicates that data was not changed.
        if (!preg_match('/^\*+$/', $value) && !empty($value)) {
            if ($value != '' && !$this->uuidValidator->isValid($value)) {
                throw new \Magento\Framework\Exception\ValidatorException(__('Invalid api key format'));
            }

            $business_profile_authentication_request = new \Synerise\ApiClient\Model\BusinessProfileAuthenticationRequest([
                'api_key' => $value
            ]);

            try {
                $this->apiHelper->getAuthApiInstance()->profileLoginUsingPOST($business_profile_authentication_request);
            } catch (\Synerise\ApiClient\ApiException $e) {
                if ($e->getCode() === 401) {
                    throw new \Magento\Framework\Exception\ValidatorException(
                        __('Test request failed. Please make sure this a valid, profile scoped api key and try again.')
                    );
                } else {
                    $this->logger->error('Synerise Api request failed', ['exception' => $e]);
                    throw $e;
                }
            }

            $this->setValue($value);
        }

        parent::beforeSave();
    }

//    public function afterSave()
//    {
//        if ($this->isValueChanged() && $this->getScope() == ScopeInterface::SCOPE_WEBSITES) {
//            $this->addStatusItemsForWebsiteId($this->getScopeId());
//        }
//
//        return parent::afterSave();
//    }
//
//    protected function addStatusItemsForWebsiteId($websiteId)
//    {
//        $defaultStoreId = $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
//        $websiteStoreIds = $this->storeWebsiteRelation->getStoreByWebsiteId($websiteId);
//
//        foreach (\Synerise\Integration\Helper\Config::MODELS as $model) {
//            Switch ($model) {
//                case 'customer':
//                    $websiteIds = explode(',', $this->_config->getValue("synerise/$model/websites"));
//                    if (in_array($websiteId, $websiteIds)) {
//                        $this->statusResourceModel->insertOrUpdate($model, $defaultStoreId, $websiteId);
//                    }
//                    break;
//                default:
//                    $storeIds = explode(',', $this->_config->getValue("synerise/$model/stores"));
//                    $enabledStoreIds = array_intersect($storeIds, $websiteStoreIds);
//                    if(!empty($enabledStoreIds)) {
//                        $this->statusResourceModel->enableByModel($model, $enabledStoreIds);
//                    }
//            }
//        }
//    }
}
