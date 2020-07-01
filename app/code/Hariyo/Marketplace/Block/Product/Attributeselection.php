<?php

namespace Hariyo\Marketplace\Block\Product;

class Attributeselection extends \Hariyo\Marketplace\Block\Product\Base {
    
    private $_default_attribute_set_id = '';
    
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setsFactory,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Magento\Framework\Registry $registry
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $setsFactory, $objectManager, $registry);
        $this->setTemplate('product/attribute_selection.phtml');
    }
    
    public function getAttributeSet() {
        if ($this->_coreRegistry->registry('vssmp_current_product')) {
            $entityType = $this->_coreRegistry->registry('vssmp_current_product')->getResource()->getEntityType();
            $this->_default_attribute_set_id = $entityType->getDefaultAttributeSetId();
            return $this->_setsFactory->create()->setEntityTypeFilter($entityType->getId())->load()->toOptionArray();
        } else {
            return parent::getAttributeSet();
        }
    }

    public function getDefaultAttributeSetId() {
        return $this->_default_attribute_set_id;
    }

    public function getContinueUrl() {
        return $this->getUrl('*/*/new');
    }

}
