<?php

namespace Knowband\Marketplace\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;

class GeneralSettings extends \Magento\Backend\Block\Template {
    
    const DEFAULT_SECTION_BLOCK = 'Magento\Config\Block\System\Config\Form';
    public function __construct(
            Context $context,
            \Knowband\Marketplace\Helper\Setting $mp_settingHelper
            )
    {
        $this->mp_settingHelper = $mp_settingHelper;
        parent::__construct($context);
    }
    
    protected function _prepareLayout()
    {
        $this->_formBlockName = self::DEFAULT_SECTION_BLOCK;
        $this->getToolbar()->addChild(
            'save_button', 'Magento\Backend\Block\Widget\Button', [
                'id' => 'save-marketplace-general',
                'label' => __('Save Settings'),
                'class' => 'save primary',
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'save', 'target' => '#vss_marketplace_view']],
                ]
            ]
        );
        
        $block = $this->getLayout()->createBlock($this->_formBlockName);
        $this->setChild('form', $block);
        return parent::_prepareLayout();
    } 
    
    
    public function getSettings($key)
    {
        return $this->mp_settingHelper->getSettings($key);
    }    
}


