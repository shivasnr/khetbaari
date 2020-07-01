<?php

namespace Hariyo\Marketplace\Block;

class Reasonpop extends \Magento\Framework\View\Element\Template {

    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Framework\Registry $registry,
            \Hariyo\Marketplace\Helper\Setting $mpSettingHelper, 
            \Psr\Log\LoggerInterface $mpLogHelper, 
            \Hariyo\Marketplace\Model\Seller $mpSellerModel
    ) {
        $this->mp_settingsHelper = $mpSettingHelper;
        $this->mp_logHelper = $mpLogHelper;
        $this->mp_sellerModel = $mpSellerModel;
        $this->_coreRegistry = $registry;
        parent::__construct($context);
        $this->setTemplate('reason_popup.phtml');
    }

    public function getMinimumLengthMsg() {
        return sprintf(__('Minimum %d character requried'), \Hariyo\Marketplace\Model\Reason::REASON_MIN_LENGTH);
    }

    public function getHeading() {
        return __('Why do you want to do this?');
    }

}
