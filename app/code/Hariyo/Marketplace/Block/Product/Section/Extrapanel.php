<?php

namespace Hariyo\Marketplace\Block\Product\Section;

class Extrapanel extends \Magento\Framework\View\Element\Template {
    
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context
    ) {
        parent::__construct($context);
        $this->setTemplate('product/section/extrapanel.phtml');
    }

}
