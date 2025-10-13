<?php

namespace Synerise\Integration\Model\Config\Backend;

use Exception;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;

class Workspaces extends Value
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param SerializerInterface $serializer
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
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->serializer = $serializer;

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
        $this->setValue($this->serializer->serialize($value));

        $encodedOldValue =  $this->getOldValue();
        $oldValue = $encodedOldValue ? $this->serializer->unserialize($encodedOldValue) : [];

        if (!empty($value)) {
            foreach ($value as $websiteId => $workspaceId) {
                if (!isset($oldValue[$websiteId]) || $oldValue[$websiteId] != $workspaceId) {
                    $this->_eventManager->dispatch(
                        'synerise_workspace_mapping_changed',
                        [
                            'workspace_id' => $workspaceId,
                            'website_id' => $websiteId
                        ]
                    );
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
        $this->setValue(!empty($decodedValue) ? $decodedValue : null);
    }
}
