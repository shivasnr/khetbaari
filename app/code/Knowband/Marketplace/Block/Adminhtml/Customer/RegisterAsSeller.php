<?php

namespace Knowband\Marketplace\Block\Adminhtml\Customer;

use Magento\Customer\Controller\RegistryConstants;
use Magento\Ui\Component\Layout\Tabs\TabInterface;

/**
 * Customer account form block
 */
class RegisterAsSeller extends \Magento\Backend\Block\Template implements TabInterface {

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
            \Magento\Backend\Block\Template\Context $context, 
            \Magento\Framework\Registry $registry, 
            \Magento\Framework\Data\FormFactory $formFactory, 
            \Magento\Store\Model\System\Store $systemStore, 
            \Knowband\Marketplace\Helper\Setting $mpSettingHelper,
            \Knowband\Marketplace\Model\Seller $mpSellerModel,
            \Knowband\Marketplace\Model\Settings $mpSettingsModel,
            array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_systemStore = $systemStore;
        $this->mp_settingHelper = $mpSettingHelper;
        $this->_formKey = $context->getFormKey();
        $this->mp_sellerModel = $mpSellerModel;
        $this->mp_settingsModel = $mpSettingsModel;
        parent::__construct($context, $data);
    }

    /**
     * @return string|null
     */
    public function getCustomerId() {
        return $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel() {
        return __('Register as Seller');
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle() {
        return __('Register as Seller');
    }

    /**
     * @return bool
     */
    public function canShowTab() {
        $settings = $this->mp_settingHelper->getSettings();
        if(!isset($settings['enable_mp']) || $settings['enable_mp'] == 0){
            return false;
        }
        
        $current_cust_id = $this->getCustomerId();
        if ($current_cust_id) {
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    public function isHidden() {
        // Code to hide the tab when disabled from settings
        $settings = $this->mp_settingHelper->getSettings();
        if(isset($settings['enable_mp']) && $settings['enable_mp'] == 0){
            return true;
        }
        
        $current_cust_id = $this->getCustomerId();
        if ($current_cust_id) {
            return true;
        }
        return false;
    }

    /**
     * Tab class getter
     *
     * @return string
     */
    public function getTabClass() {
        return '';
    }

    /**
     * Return URL link to Tab content
     *
     * @return string
     */
    public function getTabUrl() {
        return '';
    }

    /**
     * Tab should be loaded trough Ajax call
     *
     * @return bool
     */
    public function isAjaxLoaded() {
        return false;
    }
    
    public function getSellerSettings(){
        
        $collection = $this->mp_settingModel->getCollection()->addFieldToFilter('seller_id', $seller_id);
        $globalSettings = $this->mp_settingHelper->getSettings();

        $settings = [];
        foreach ($collection as $row) {
            if ($row->getFieldValue() != '') {
                $settings[$row->getFieldName()]['seller'] = $row->getFieldValue();
            } else {
                if ($row->getFieldName() == 'commission') {
                    $settings[$row->getFieldName()]['seller'] = $globalSettings[$row->getFieldName()];
                }
            }

            $settings[$row->getFieldName()]['global'] = $row->getUseDefault();
        }

        if(empty($settings) ){
            $settings = $this->mp_settingHelper->getDefaultSellerSettings();
        }
        unset($globalSettings);
        unset($collection);
        
        return $settings;
    }
    
    public function isRegisterAsSeller(){
        $settings = $this->mp_settingHelper->getSettings();
        if (!$this->getCustomerId() && isset($settings['enable_mp']) && $settings['enable_mp'] == 1) {
            return true;
        }
        return false;
    }
}
