<?php

namespace Hariyo\Marketplace\Block\Product\Type;

class Configurable extends \Hariyo\Marketplace\Block\Product\Base 
{
    protected $hasAttributes = false;
    protected $attributes = [];
    
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
        $this->setTemplate('product/attribute_settings.phtml');
    }
    
    protected function _prepareLayout() {
        $product = $this->getProduct();
        $attributes = $product->getTypeInstance(true)->getSetAttributes($product);

        foreach ($attributes as $attribute) {
            if ($product->getTypeInstance(true)->canUseAttribute($attribute, $product)) {
                $this->attributes[] = [
                    'id' => $attribute->getAttributeId(),
                    'label' => $attribute->getFrontend()->getLabel()
                ];
                $this->hasAttributes = true;
            }
        }
        return parent::_prepareLayout();
    }   
    
    public function hasAttributes() {
        return $this->hasAttributes;
    }

    public function getAttributes() {
        return $this->attributes;
    }

    /**
     * Retrieve Continue URL
     *
     * @return string
     */
    public function getContinueUrl() {
        return $this->getUrl('marketplace/product/new');
    }

}
