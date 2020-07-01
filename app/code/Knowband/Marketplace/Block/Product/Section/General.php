<?php

namespace Knowband\Marketplace\Block\Product\Section;

class General extends \Knowband\Marketplace\Block\Product\Base {
    
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setsFactory,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Magento\Framework\Registry $registry,
             \Knowband\Marketplace\Helper\Seller $mpSellerHelper
    ) {
        $this->mp_sellerHelper = $mpSellerHelper;
        parent::__construct($context, $setsFactory, $objectManager, $registry);
        $this->setTemplate('product/section/general.phtml');
    }
    
    public function isStatusReadOnly() {
        if (!$this->mp_sellerHelper->isApprovedSeller() || !$this->mp_sellerHelper->isEnabledSeller()) {
            return true;
        }
        return false;
    }

}
