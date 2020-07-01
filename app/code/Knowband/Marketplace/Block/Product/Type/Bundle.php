<?php

namespace Knowband\Marketplace\Block\Product\Type;

class Bundle extends \Knowband\Marketplace\Block\Product\Base 
{
    private $_blockOption;
    private $_option_index = 0;
    private $_selection_index = 0;
    /**
    * List of bundle product options
    *
    * @var array|null
    */
    protected $_options = null;
    
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setsFactory,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Magento\Framework\Registry $registry,
            \Magento\Framework\Json\Helper\Data $jsonHelper,
            \Magento\Catalog\Helper\Data $catalogData
    ) {
        $this->_coreRegistry = $registry;
        $this->_setsFactory = $setsFactory;
        $this->_objectManager = $objectManager;
        $this->_jsonHelper = $jsonHelper;
        $this->_catalogDataHelper = $catalogData;
        
        parent::__construct($context, $setsFactory, $objectManager, $registry);
        $this->setTemplate('product/section/bundle.phtml');
    }
    
    protected function _prepareLayout() {
//        $this->getLayout()->getBlock('head')->addItem('skin_js', 'Knowband_Marketplace/theme/vssmp_bundle.js');
        return parent::_prepareLayout();
    }

    public function getOptionIndex() {
        return $this->_option_index;
    }

    public function getSelectionIndex() {
        return $this->_selection_index;
    }

    public function getBlockOptionObj() {
        return $this->_objectManager->create("\Magento\Bundle\Block\Adminhtml\Catalog\Product\Edit\Tab\Bundle\Option");
    }

    public function getBundleSelectionObj() {
        return $this->_objectManager->create("\Magento\Bundle\Block\Adminhtml\Catalog\Product\Edit\Tab\Bundle\Option\Selection");
    }

    public function getTypeSelectHtml() {
        $optObj = $this->getBlockOptionObj();
        $select = $this->getLayout()->createBlock('\Magento\Framework\View\Element\Html\Select')
                ->setData([
                    'id' => $optObj->getFieldId() . '_{{option_index}}_type',
                    'class' => 'form-control',
                    'extra_params' => 'validate="int"'
                ])
                ->setName($optObj->getFieldName() . '[{{option_index}}][type]')
                ->setOptions($this->_objectManager->get("\Magento\Bundle\Model\Source\Option\Type")->toOptionArray());
        return $select->getHtml();
    }

    public function getRequireSelectHtml() {
        $optObj = $this->getBlockOptionObj();
        $select = $this->getLayout()->createBlock('\Magento\Framework\View\Element\Html\Select')
                ->setData(array(
                    'id' => $optObj->getFieldId() . '_{{option_index}}_required',
                    'class' => 'form-control'
                ))
                ->setName($optObj->getFieldName() . '[{{option_index}}][required]')
                ->setOptions($this->_objectManager->get("\Magento\Config\Model\Config\Source\Yesno")->toOptionArray());

        return $select->getHtml();
    }

    public function getPriceTypeSelectHtml() {
        $obj = $this->getBundleSelectionObj();
        $select = $this->getLayout()->createBlock('\Magento\Framework\View\Element\Html\Select')
                ->setData([
                    'id' => $obj->getFieldId() . '_{{selection_index}}_price_type',
                    'class' => 'form-control'
                ])
                ->setName($obj->getFieldName() . '[{{option_index}}][{{selection_index}}][selection_price_type]')
                ->setOptions($this->_objectManager->get("\Magento\Bundle\Model\Source\Option\Selection\Price\Type")->toOptionArray());
        return $select->getHtml();
    }

    public function isUsedWebsitePrice() {
        return !$this->_catalogDataHelper->isPriceGlobal() && $this->_coreRegistry->registry('product')->getStoreId();
    }

    public function getCheckboxScopeHtml() {
        $obj = $this->getBundleSelectionObj();
        $checkboxHtml = '';
        if ($this->isUsedWebsitePrice()) {
            $id = $obj->getFieldId() . '_{{selection_index}}_price_scope';
            $name = $obj->getFieldName() . '[{{option_index}}][{{selection_index}}][default_price_scope]';
            $class = '';
            $label = __('Use Default Value');
            $disabled = '';
            $checkboxHtml = '<input type="checkbox" id="' . $id . '" class="' . $class . '" name="' . $name
                    . '"' . $disabled . ' value="1" />';
            $checkboxHtml .= '<label class="normal" for="' . $id . '">' . $label . '</label>';
        }
        return $checkboxHtml;
    }

    public function getQtyTypeSelectHtml() {
        $obj = $this->getBundleSelectionObj();
        $select = $this->getLayout()->createBlock('\Magento\Framework\View\Element\Html\Select')
                ->setData([
                    'id' => $obj->getFieldId() . '_{{selection_index}}_can_change_qty',
                    'class' => 'form-control'
                ])
                ->setName($obj->getFieldName() . '[{{option_index}}][{{selection_index}}][selection_can_change_qty]')
                ->setOptions($this->_objectManager->get("\Magento\Config\Model\Config\Source\Yesno")->toOptionArray());

        return $select->getHtml();
    }

    /**
     * Retrieve list of bundle product options
     *
     * @return array
     */
    public function getOptions() {
        if (!$this->_options) {
            $this->getProduct()->getTypeInstance(true)->setStoreFilter($this->getProduct()->getStoreId(), $this->getProduct());

            $optionCollection = $this->getProduct()->getTypeInstance(true)->getOptionsCollection($this->getProduct());

            $selectionCollection = $this->getProduct()->getTypeInstance(true)->getSelectionsCollection(
                    $this->getProduct()->getTypeInstance(true)->getOptionsIds($this->getProduct()), $this->getProduct()
            );

            $this->_options = $optionCollection->appendSelections($selectionCollection);
            foreach ($this->_options as $option) {
                if ($option->getSelections()) {
                    foreach ($option->getSelections() as $selection) {
                        $tmp = $this->_jsonHelper->jsonDecode($selection->toJson());
                    }
                }
            }
        }
        return $this->_options;
    }
    

}
