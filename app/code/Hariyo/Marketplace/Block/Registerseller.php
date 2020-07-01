<?php

namespace Hariyo\Marketplace\Block;

class Registerseller extends \Magento\Framework\View\Element\Template {

    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context, 
            \Hariyo\Marketplace\Helper\Setting $mpSettingHelper
    ) {
        $this->mp_settingsHelper = $mpSettingHelper;
        parent::__construct($context);
    }
}
