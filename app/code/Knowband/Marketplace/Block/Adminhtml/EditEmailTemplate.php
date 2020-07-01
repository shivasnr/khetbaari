<?php

namespace Knowband\Marketplace\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;

class EditEmailTemplate extends \Magento\Backend\Block\Template {
    
    const DEFAULT_SECTION_BLOCK = 'Magento\Config\Block\System\Config\Form';
    public function __construct(
            Context $context,
            \Knowband\Marketplace\Model\Emailtemplates $mpEmailTemplates
            )
    {
        $this->mp_emailTemplates = $mpEmailTemplates;
        parent::__construct($context);
    }
    
    protected function _prepareLayout()
    {
        $this->_formBlockName = self::DEFAULT_SECTION_BLOCK;
        $this->getToolbar()->addChild(
            'save_button', 'Magento\Backend\Block\Widget\Button', [
                'id' => 'save-marketplace-template',
                'label' => __('Save Template'),
                'class' => 'save primary',
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'save', 'target' => '#vss_marketplace_view']],
                ]
            ]
        );
        
        $this->getToolbar()->addChild(
            'back_button', 'Magento\Backend\Block\Widget\Button', [
                'id' => 'back',
                'label' => __('Back'),
                'class' => 'action- scalable back',
                'onclick' => 'setLocation(\'' . $this->getUrl('mpadmin/marketplace/emailTemplates', ['_current' => true]) . '\')',
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'save', 'target' => '#vss_marketplace_view']],
                ]
            ]
        );
        
        $block = $this->getLayout()->createBlock($this->_formBlockName);
        $this->setChild('form', $block);
        return parent::_prepareLayout();
    } 
    
    public function getTemplateData(){
        $template_id = $this->getRequest()->getParam('template_id');
        $model = $this->mp_emailTemplates;
        $model->load((int) $template_id);
        $template_data = $model->getData();
        return $template_data;
    }
    
}


