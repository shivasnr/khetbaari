<?php

namespace Hariyo\Marketplace\Block\Product\Section;

class Related extends \Hariyo\Marketplace\Block\Product\Base 
{    
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setsFactory,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Magento\Framework\Registry $registry,
            \Hariyo\Marketplace\Helper\Product $mpProductHelper
    ) {
        $this->_coreRegistry = $registry;
        $this->_setsFactory = $setsFactory;
        $this->_objectManager = $objectManager;
        $this->mp_productHelper = $mpProductHelper;
        
        parent::__construct($context, $setsFactory, $objectManager, $registry);
        $this->setTemplate('product/section/related.phtml');
    }
    
    public function getSelectedRelatedProducts(){
        return $this->mp_productHelper->getSelectedRelatedProducts();
    }

}
