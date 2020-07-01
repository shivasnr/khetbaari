<?php

namespace Knowband\Marketplace\Block\Product\Section;

class Section extends \Knowband\Marketplace\Block\Product\Base {
    
    CONST SHORT_DESCRIPTION_LENGTH = 250;
    CONST LONG_DESCRIPTION_LENGTH = 1500;
	
    private $_extra_section = [];
    
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setsFactory,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Magento\Framework\Registry $registry,
            \Knowband\Marketplace\Helper\Fields $mpFieldsHelper
    ) {
        $this->_coreRegistry = $registry;
        $this->_setsFactory = $setsFactory;
        $this->_objectManager = $objectManager;
        $this->mp_fieldsHelper = $mpFieldsHelper;
        
        parent::__construct($context, $setsFactory, $objectManager, $registry);
        $this->setTemplate('product/section/section.phtml');
        
        $this->_extra_section = [
            'associate_section' => [
                \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE,
                \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE
            ],
            'attribute_section' => [
                \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE,
                \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE,
                \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE,
                'downloadable'
            ],
            'bundle_section' => [\Magento\Catalog\Model\Product\Type::TYPE_BUNDLE],
            'download_section' => ['downloadable']
        ];

        $product = $this->getProduct();
        if ($product && $product->getId()) {
            $set_id = $product->getAttributeSetId();
        } else {
            $set_id = $this->getRequest()->getParam('set');
        }


        $fields = $this->mp_fieldsHelper->getMandatoryFields($set_id);

        $this->_coreRegistry->register('current_product_fields', $fields);
    }
    
    protected function _prepareLayout() {
        if ($this->getProduct() && $this->getProduct()->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            $this->setChild('quick_create', $this->getLayout()->createBlock('\Knowband\Marketplace\Block\Product\Section\Quickcreate', 'marketplace.product.section.quickcreate')
            );
        }
    }

    public static function getShortDescriptionLength() {
        return self::SHORT_DESCRIPTION_LENGTH;
    }

    public static function getLongDescriptionLength() {
        return self::LONG_DESCRIPTION_LENGTH;
    }

    public function getQuickCreateHtml() {
        return $this->getChild('quick_create');
    }

    public function isAttributeSectionVisible() {
        $product = $this->getProduct();
        if (in_array($product->getTypeId(), $this->_extra_section['attribute_section'])) {
            return true;
        }
        return false;
    }

    public function isAssociateSectionVisible() {
        $product = $this->getProduct();
        if (in_array($product->getTypeId(), $this->_extra_section['associate_section'])) {
            return true;
        }
        return false;
    }

    public function isBundleSectionVisible() {
        $product = $this->getProduct();
        if (in_array($product->getTypeId(), $this->_extra_section['bundle_section'])) {
            return true;
        }
        return false;
    }

    public function isDownloadableSectionVisible() {
        $product = $this->getProduct();
        if (in_array($product->getTypeId(), $this->_extra_section['download_section'])) {
            return true;
        }
        return false;
    }

}
