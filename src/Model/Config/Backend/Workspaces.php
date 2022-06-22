<?php

namespace Synerise\Integration\Model\Config\Backend;

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
            foreach($value as $websiteId => $workspaceId) {
                if (!isset($oldValue[$websiteId]) || $oldValue[$websiteId] != $workspaceId) {
                    $this->saveLinkedConfig($websiteId, $workspaceId);
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
        /** @var string $value */
        $value = $this->getValue();
        $decodedValue = $value ? $this->serializer->unserialize($value) : [];

        $websites = $this->websiteCollectionFactory->create();
        foreach($websites as $website) {
            if (!isset($decodedValue[$website->getId()])) {
                $decodedValue[$website->getId()] = [''];
            }
        }

        $this->setValue(!empty($decodedValue) ? $decodedValue : null);
    }

    protected function saveLinkedConfig($websiteId, $workspaceId)
    {
        $configData = [
            'section' => 'synerise_workspace',
            'website' => $websiteId,
            'store' => null,
            'groups' => [
                'workspace' => [
                    'fields' => [
                        'id' => [
                            'value' => $workspaceId
                        ]
                    ]
                ]
            ],
        ];

        /** @var \Magento\Config\Model\Config $configModel */
        $configModel = $this->configFactory->create(['data' => $configData]);
        $configModel->save();
    }
}
