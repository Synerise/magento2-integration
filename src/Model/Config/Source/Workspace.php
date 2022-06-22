<?php

namespace Synerise\Integration\Model\Config\Source;

use Synerise\Integration\Model\ResourceModel\Workspace\CollectionFactory;

class Workspace implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    public function toOptionArray()
    {
        $options = [
            [
                'value' => '',
                'label' => ' '
            ]
        ];

        $profiles = $this->collectionFactory->create();
        /** @var \Synerise\Integration\Model\Workspace $profile */
        foreach ($profiles as $profile) {
            $options[] = [
                'value' => $profile->getId(),
                'label' => $profile->getName()
            ];
        }

        return $options;
    }
}