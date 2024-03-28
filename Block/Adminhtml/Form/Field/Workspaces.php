<?php

namespace Synerise\Integration\Block\Adminhtml\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;
use Synerise\Integration\Model\ResourceModel\Workspace\Collection;
use Synerise\Integration\Model\ResourceModel\Workspace\CollectionFactory as WorkspaceCollectionFactory;

class Workspaces extends Field
{
    /**
     * @var WebsiteCollectionFactory
     */
    protected $websiteCollectionFactory;

    /**
     * @var WorkspaceCollectionFactory
     */
    protected $workspaceCollectionFactory;

    /**
     * @param Context $context
     * @param WebsiteCollectionFactory $websiteCollectionFactory
     * @param WorkspaceCollectionFactory $workspaceCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        WebsiteCollectionFactory $websiteCollectionFactory,
        WorkspaceCollectionFactory $workspaceCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->websiteCollectionFactory = $websiteCollectionFactory;
        $this->workspaceCollectionFactory = $workspaceCollectionFactory;

        $this->setTemplate('Synerise_Integration::form/field/workspaces.phtml');
    }

    /**
     * @inheritDoc
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setElement($element);
        return $this->_toHtml();
    }

    /**
     * Get table rows with website data.
     *
     * @return array
     */
    public function getRows(): array
    {
        $values = (array) $this->getElement()->getValue();

        $rows = [];
        $websitesCollection = $this->websiteCollectionFactory->create();
        foreach ($websitesCollection as $website) {
            $rows[$website->getId()] = [
                'id' => $website->getId(),
                'name' => $website->getName(),
                'value' => !empty($values[$website->getId()]) ? $values[$website->getId()] : ''
            ];
        }

        return $rows;
    }

    /**
     * Get workspaces collection.
     *
     * @return Collection
     */
    public function getWorkspaces(): Collection
    {
        return $this->workspaceCollectionFactory->create();
    }
}
