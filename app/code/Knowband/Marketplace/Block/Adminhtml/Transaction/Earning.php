<?php

namespace Knowband\Marketplace\Block\Adminhtml\Transaction;

class Earning extends \Magento\Backend\Block\Template {

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        $this->_controller = 'adminhtml_marketplace';
        $this->_blockGroup = 'Knowband_Marketplace';
        parent::_construct();
    }
}


