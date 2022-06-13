<?php

namespace Synerise\Integration\Block\Adminhtml\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;
use Synerise\Integration\Model\ResourceModel\BusinessProfile\CollectionFactory as BusinessProfileCollectionFactory;

class BusinessProfiles extends Field
{
    /**
     * @var WebsiteCollectionFactory 
     */
    protected $websiteCollectionFactory;

    /**
     * @var BusinessProfileCollectionFactory 
     */
    protected $businessProfileCollectionFactory;

    public function __construct(
        Context $context,
        WebsiteCollectionFactory $websiteCollectionFactory,
        BusinessProfileCollectionFactory $businessProfileCollectionFactory,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        $this->websiteCollectionFactory = $websiteCollectionFactory;
        $this->businessProfileCollectionFactory = $businessProfileCollectionFactory;

        $this->setTemplate('Synerise_Integration::form/field/business_profiles.phtml');

        parent::__construct($context, $data, $secureRenderer);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setElement($element);
        return $this->_toHtml();
    }

    public function getRows()
    {
        $values = (array) $this->getElement()->getValue();

        $rows = [];
        $websitesCollection = $this->websiteCollectionFactory->create();
        foreach($websitesCollection as $website) {
            $rows[$website->getId()] = [
                'id' => $website->getId(),
                'name' => $website->getName(),
                'value' => !empty($values[$website->getId()]) ? $values[$website->getId()] : ''
            ];
        }

        return $rows;
    }

    public function getBusinessProfiles()
    {
        return $this->businessProfileCollectionFactory->create();
    }

}