<?php

namespace Knowband\Marketplace\Block\Adminhtml\Customer;

use Magento\Customer\Controller\RegistryConstants;
use Magento\Ui\Component\Layout\Tabs\TabInterface;

/**
 * Customer account form block
 */
class ManageSellerSettings extends \Magento\Backend\Block\Template implements TabInterface {

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
        $this->mp_settingModel = $mpSettingsModel;
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
        return __('Manage Seller Account');
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle() {
        return __('Manage Seller Account');
    }

    /**
     * @return bool
     */
    public function canShowTab() {
        // Code to hide the tab when disabled from settings
        $settings = $this->mp_settingHelper->getSettings();
        if (!isset($settings['enable_mp']) || $settings['enable_mp'] == 0) {
            return false;
        } else {
            $current_cust_id = $this->getCustomerId();
            if (!$current_cust_id) {
                return false;
            }


            $seller = $this->mp_sellerModel->load($current_cust_id, 'seller_id');
            $seller_id = $seller->getSellerId();
            $seller->unsetData();
            if ($seller_id != $current_cust_id) {
                return false;
            }
            return true;
        }
    }

    /**
     * @return bool
     */
    public function isHidden() {
        // Code to hide the tab when disabled from settings
        $settings = $this->mp_settingHelper->getSettings();
        if (isset($settings['enable_mp']) && $settings['enable_mp'] == 0) {
            return true;
        } else {
            $current_cust_id = $this->getCustomerId();
            if (!$current_cust_id) {
                return true;
            }
            $seller = $this->mp_sellerModel->load($current_cust_id, 'seller_id');
            $seller_id = $seller->getSellerId();
            $seller->unsetData();
            if ($seller_id != $current_cust_id) {
                return true;
            }
            return false;
        }
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

    public function getSellerSettings() {
        $seller_id = $this->getCustomerId();
        $collection = $this->mp_settingModel->getCollection()->addFieldToFilter('seller_id', $seller_id);
        $globalSettings = $this->mp_settingHelper->getSettings();

        $settings = [];
        if ($collection->getSize()) {
            foreach ($collection as $row) {
                if ($row->getFieldValue() != '') {
                    $settings[$row->getFieldName()]['seller'] = $row->getFieldValue();
                } else {
                    if ($row->getFieldName() == 'commission') {
                        $settings[$row->getFieldName()]['seller'] = $globalSettings[$row->getFieldName()];
                    } else {
                        $settings[$row->getFieldName()]['seller'] = isset($globalSettings[$row->getFieldName()]) ? $globalSettings[$row->getFieldName()] : '';
                    }
                }

                $settings[$row->getFieldName()]['global'] = $row->getUseDefault();
            }
        } else {
            $settings['commission']['seller'] = isset($globalSettings["commission"]) ? $globalSettings["commission"] : 15;
            $settings['commission']['global'] = 1;
        }

        if (empty($settings)) {
            $settings = $this->mp_settingHelper->getDefaultSellerSettings();
        }
        unset($globalSettings);
        unset($collection);

        return $settings;
    }

}
