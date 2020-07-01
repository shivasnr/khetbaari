<?php

namespace Knowband\Marketplace\Block\Earnings;

class Home extends \Magento\Framework\View\Element\Template {

    private $order_status = [];
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Knowband\Marketplace\Helper\Setting $mpSettingHelper
    ) {
        $this->mp_settingsHelper = $mpSettingHelper;
        parent::__construct($context);
        $this->setTemplate('earnings/home.phtml');
        
        $statuses = $this->mp_settingsHelper->getGlobalSettingByKey('order_statuses');
        if (is_array($statuses) && !empty($statuses)) {
            $this->order_status = $statuses;
        }
    }
    
    public function getOrderStatuses() {
        return $this->order_status;
    }

}
