<?php

namespace Knowband\Marketplace\Block\Adminhtml\Transaction;

class SellerEarning extends \Magento\Backend\Block\Template {

    const DEFAULT_SECTION_BLOCK = 'Magento\Config\Block\System\Config\Form';
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Knowband\Marketplace\Helper\Data $mpDataHelper,
        array $data = []
    ) {
        $this->mp_dataHelper = $mpDataHelper;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        $this->_controller = 'adminhtml_marketplace';
        $this->_blockGroup = 'Knowband_Marketplace';
        parent::_construct();
    }
}


