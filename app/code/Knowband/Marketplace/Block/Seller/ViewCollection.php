<?php

namespace Knowband\Marketplace\Block\Seller;

class ViewCollection extends \Magento\Framework\View\Element\Template {

    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Knowband\Marketplace\Model\Seller $mpSellerModel,
            \Knowband\Marketplace\Helper\Data $mpDataHelper
    ) {
        $this->_objectManager = $objectManager;
        $this->mp_sellerModel = $mpSellerModel;
        $this->_storeManager = $context->getStoreManager();
        $this->mp_dataHelper = $mpDataHelper;
        $this->scopeConfig = $context->getScopeConfig();
        parent::__construct($context);
    }
    
    protected function _prepareLayout() {
        parent::_prepareLayout();
        return $this;
    }
}
