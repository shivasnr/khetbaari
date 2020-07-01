<?php

namespace Hariyo\Marketplace\Block\Product\Section;

class Price extends \Hariyo\Marketplace\Block\Product\Base 
{    
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setsFactory,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Magento\Framework\Registry $registry
    ) {
        $this->_coreRegistry = $registry;
        $this->_setsFactory = $setsFactory;
        $this->_objectManager = $objectManager;
        
        parent::__construct($context, $setsFactory, $objectManager, $registry);
        $this->setTemplate('product/section/price.phtml');
    }

}
