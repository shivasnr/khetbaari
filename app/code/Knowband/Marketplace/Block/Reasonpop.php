<?php

namespace Knowband\Marketplace\Block;

class Reasonpop extends \Magento\Framework\View\Element\Template {

    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Framework\Registry $registry,
            \Knowband\Marketplace\Helper\Setting $mpSettingHelper, 
            \Knowband\Marketplace\Helper\Log $mpLogHelper, 
            \Knowband\Marketplace\Model\Seller $mpSellerModel
    ) {
        $this->mp_settingsHelper = $mpSettingHelper;
        $this->mp_logHelper = $mpLogHelper;
        $this->mp_sellerModel = $mpSellerModel;
        $this->_coreRegistry = $registry;
        parent::__construct($context);
        $this->setTemplate('reason_popup.phtml');
    }

    public function getMinimumLengthMsg() {
        return sprintf(__('Minimum %d character requried'), \Knowband\Marketplace\Model\Reason::REASON_MIN_LENGTH);
    }

    public function getHeading() {
        return __('Why do you want to do this?');
    }

}
