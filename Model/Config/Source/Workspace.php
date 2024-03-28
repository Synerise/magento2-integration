<?php

namespace Synerise\Integration\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Synerise\Integration\Model\ResourceModel\Workspace\CollectionFactory;

class Workspace implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
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
