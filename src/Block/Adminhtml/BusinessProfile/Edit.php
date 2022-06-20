<?php
namespace Synerise\Integration\Block\Adminhtml\BusinessProfile;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Initialize Business Profile edit block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Synerise_Integration';
        $this->_controller = 'adminhtml_businessProfile';

        parent::_construct();

        if ($this->_isAllowedAction('Synerise_Integration::business_profile_add')) {
            $this->buttonList->update('save', 'label', __('Save Business Profile'));
        } else {
            $this->buttonList->remove('save');
        }

        if ($this->_isAllowedAction('Synerise_Integration::business_profile_delete')) {
            $this->buttonList->update('delete', 'label', __('Delete Business Profile'));
        } else {
            $this->buttonList->remove('delete');
        }
    }

    /**
     * Retrieve text for header element depending on loaded Business Profile
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __('New Business Profile');
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * Getter of url for "Save and Continue" button
     * tab_id will be replaced by desired by JS later
     *
     * @return string
     */
    protected function _getSaveAndContinueUrl()
    {
        return $this->getUrl('businessprofile/*/save', ['_current' => true, 'back' => 'edit', 'active_tab' => '']);
    }
}