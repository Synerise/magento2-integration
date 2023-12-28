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

        $workspaces = $this->collectionFactory->create();
        /** @var \Synerise\Integration\Model\Workspace $workspace */
        foreach ($workspaces as $workspace) {
            $options[] = [
                'value' => $workspace->getId(),
                'label' => $workspace->getName()
            ];
        }

        return $options;
    }
}
