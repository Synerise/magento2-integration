<?php
namespace Synerise\Integration\Block\Adminhtml\BusinessProfile\Edit;


/**
 * Adminhtml blog post edit form
 */
class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * Init form
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('businessprofile_form');
        $this->setTitle(__('Business Profile Information'));
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Synerise\Integration\Model\BusinessProfile $model */
        $model = $this->getDataObject();

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );

        $form->setHtmlIdPrefix('businessprofile_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Business Profile'), 'class' => 'fieldset-wide']
        );

        if ($model && $model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }

        $fieldset->addField(
            'api_key',
            'obscure',
            ['name' => 'api_key', 'label' => __('Api Key'), 'title' => __('Api Key'), 'required' => true]
        );

        if($model) {
            $form->setValues($model->getData());
        }
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}