<?php

namespace Synerise\Integration\Model\Config\Backend;

use Exception;
use Magento\Config\Model\Config\Factory as ConfigFactory;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;

class Workspaces extends Value
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var WebsiteCollectionFactory
     */
    protected $websiteCollectionFactory;

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param SerializerInterface $serializer
     * @param WebsiteCollectionFactory $websiteCollectionFactory
     * @param ConfigFactory $configFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        SerializerInterface $serializer,
        WebsiteCollectionFactory $websiteCollectionFactory,
        ConfigFactory $configFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->serializer = $serializer;
        $this->websiteCollectionFactory = $websiteCollectionFactory;
        $this->configFactory = $configFactory;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Saved linked config before save
     *
     * @return void
     * @throws Exception
     */
    public function beforeSave()
    {
        /** @var array $value */
        $value = $this->getValue();
        unset($value['__empty']);
        $encodedValue = $this->serializer->serialize($value);
        $this->setValue($encodedValue);

        $encodedOldValue =  $this->getOldValue();
        $oldValue = $encodedOldValue ? $this->serializer->unserialize($encodedOldValue) : [];

        if (!empty($value)) {
            foreach ($value as $websiteId => $workspaceId) {
                if (!isset($oldValue[$websiteId]) || $oldValue[$websiteId] != $workspaceId) {
                    $this->saveLinkedConfig($websiteId, (int) $workspaceId);
                }
            }
        }

        parent::beforeSave();
    }

    /**
     * Process data after load
     *
     * @return void
     */
    protected function _afterLoad()
    {
        $value = $this->getValue();
        $decodedValue = $value ? $this->serializer->unserialize($value) : [];

        $websites = $this->websiteCollectionFactory->create();
        foreach ($websites as $website) {
            if (!isset($decodedValue[$website->getId()])) {
                $decodedValue[$website->getId()] = [''];
            }
        }

        $this->setValue(!empty($decodedValue) ? $decodedValue : null);
    }

    /**
     * Save workspace id for each website (config path: synerise/workspace/id)
     *
     * @param int $websiteId
     * @param int $workspaceId
     * @return void
     * @throws Exception
     */
    protected function saveLinkedConfig(int $websiteId, int $workspaceId)
    {
        $configData = [
            'section' => 'synerise_workspace',
            'website' => $websiteId,
            'store' => null,
            'groups' => [
                'websites' => [
                    'fields' => [
                        'id' => [
                            'value' => $workspaceId
                        ]
                    ]
                ]
            ],
        ];

        $this->configFactory->create(['data' => $configData])->save();
    }
}
