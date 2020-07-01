<?php

namespace Knowband\Marketplace\Block\Product;

class Base extends \Magento\Framework\View\Element\Template {
    
    private $_front_form_date_format = 'm/d/Y';
    
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setsFactory,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Magento\Framework\Registry $registry
    ) {
        $this->_coreRegistry = $registry;
        $this->_setsFactory = $setsFactory;
        $this->_objectManager = $objectManager;
        parent::__construct($context);
        
    }
    
    public function getProduct() {
        return $this->_coreRegistry->registry('vssmp_current_product');
    }

    public function hasAttributeSet() {
        $product = $this->getProduct();
        if (!$product->getAttributeSetId()) {
            return false;
        }
        return true;
    }

    public function getAttributeSetId() {
        return $this->getProduct()->getAttributeSetId();
    }

    public function getUsedProductAttributes() {
        $attrs = [];
        $product = $this->getProduct();
        foreach ($product->getTypeInstance(true)->getUsedProductAttributes($product) as $attribute) {
            $attrs[] = $attribute->getAttributeCode();
        }
        return $attrs;
    }

    public function getUsedProductIds() {
        $product = $this->getProduct();
        $products = $this->getRequest()->getPost('products', null);
        if (!is_array($products)) {
            $products = $product->getTypeInstance(true)->getUsedProductIds($product);
        }
        return $products;
    }

    public function getAttributes() {
        $entityType = $this->getProduct()->getResource()->getEntityType();
        $sets = $this->_setsFactory->create()->setEntityTypeFilter(
            $entityType->getId()
        )->load()->toOptionArray();
        return $sets;
    }

    public function getFormHeader() {
        $header = '';
        if ($this->getProduct()->getId()) {
            $header = $this->escapeHtml($this->getProduct()->getName());
        } else {
            $header = __('New Product');
        }
        if ($setName = $this->getAttributeSetName()) {
            $header.= ' (' . $setName . ')';
        }
        return $header;
    }

    public function getAttributeSetName() {
        $setId = $this->getProduct()->getAttributeSetId();
        if ($setId) {
            $set = $this->_objectManager->create('\Magento\Eav\Model\Entity\Attribute\Set');
            $set->load($setId);
            $setName =  $set->getAttributeSetName();
            $set->unsetData();
            return $setName;
        }
        return '';
    }

    public function getFormDateFormat($dateString = '') {
        if ($dateString != '') {
            return date($this->_front_form_date_format, strtotime($dateString));
        } else {
            return date($this->_front_form_date_format, time());
        }
    }

    public function getAttributeSet() {
        $storeManager = $this->_objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $storeID = $storeManager->getStore()->getStoreId();
        $entityType = $this->_objectManager->create('\Magento\Catalog\Model\Product')
                        ->setStoreId($storeID)
                        ->getResource()->getEntityType();
        return $this->_setsFactory->create()->setEntityTypeFilter($entityType->getId())->load()->toOptionArray();
    }

    public function getProductTypes() {
        $all_types = $this->_objectManager->get('\Magento\Catalog\Model\Product\Type')->getOptionArray();
        $result = array_slice($all_types, 0, 1);
        return $result;
    }

    public function getStockStatuses() {
        return $this->_objectManager->get('\Magento\CatalogInventory\Model\Source\Stock')->getAllOptions();
    }

    public function isReadOnly($key) {
        if ($key == 'status') {
            $sellerHelper = $this->_objectManager->get('\Knowband\Marketplace\Helper\Seller');
            if (!$sellerHelper->isApprovedSeller() || !$sellerHelper->isEnabledSeller() || $this->isProductApprovalRequired()) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if the product approval required for seller product
     * @return boolean
     */
    public function isProductApprovalRequired() {
        try {
            $settingHelper = $this->_objectManager->get('\Knowband\Marketplace\Helper\Setting');
            $sellerInfo = $this->_coreRegistry->registry('vssmp_seller_info');
            $enableProductApproval = $settingHelper->getSettingByKey($sellerInfo['entity_id'], 'product_approval');

            $productId = $this->getProduct()->getId();
            if ($productId) {
                $prod_to_seller_model = $this->_objectManager->create('\Knowband\Marketplace\Model\Product')->load($productId, 'product_id');
                $prod_approved = $prod_to_seller_model->getApproved();
                $prod_to_seller_model->unsetData();
                if ($prod_approved == \Knowband\Marketplace\Helper\GridAction::APPROVED) {
                    return false;
                } else {
                    return true;
                }
            } else {
                if (!$enableProductApproval) {
                    return false;
                } else {
                    return true;
                }
            }
        } catch (\Exception $ex) {
            return true;
        }
    }

    public function renderFieldHtml($field, $input_name = 'product', $has_event = false) {
        $html = '';
        $required = (($field['is_required']) ? 'required' : '');

        $date_field = (($field['field_input'] == 'date') ? 'has_datepicker' : '');
        $field_helper = $this->_objectManager->get('\Knowband\Marketplace\Helper\Fields');
        $product = $this->getProduct();

        if ($product->getId()) {
            $value = $product->getData($field['code']);
        } else {
            $value = '';
        }
        if ($field['field_input'] == 'date' && !empty($value)) {
            $value = $this->getFormDateFormat($value);
        }

        if ($field['field_input'] == 'textarea') {
            $maxlength = '';
            if ($field['code'] == 'short_description') {
                $maxlength .= 'maxlength="' . \Knowband\Marketplace\Block\Product\Section\Section::getShortDescriptionLength() . '"';
            } else if ($field['code'] == 'description') {
                $maxlength .= 'maxlength="' . \Knowband\Marketplace\Block\Product\Section\Section::getLongDescriptionLength() . '"';
            }
            $html .= '<textarea ' . $maxlength . ' replace_extras rows="5" name="' . $input_name . '[' . $field['code'] . ']"  class="form-control ' . $required . '" validate="' . $field['field_type'] . '">' . $value . '</textarea>';
        } else if ($field['field_input'] == 'select' || $field['field_input'] == 'multiselect') {
            $default_value = $field['default_value'];
            if($field['code'] == 'status' && $this->isReadOnly($field['code'])){
                $default_value = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED;
            }
            if ($product->getId()) {
                $default_value = $product->getData($field['code']);
            }

            $html .= '<select replace_extras name="' . $input_name . '[' . $field['code'] . ']" class="form-control ' . $required . '" validate="' . $field['field_type'] . '" ' . (($this->isReadOnly($field['code'])) ? 'disabled="disabled"' : '') . ' >';
            if (isset($field['values']) && is_array($field['values'])) {
                $list = $field['values'];
                foreach ($list as $val) {
                    $html .= '<option value="' . $val['value'] . '" ' . (($default_value == $val['value']) ? 'selected="selected"' : '') . ' >' . $val['label'] . '</option>';
                }
            } else {
                $list = $field_helper->getDropDownData($field['source_model']);
                foreach ($list as $key => $val) {
                    $html .= '<option value="' . $key . '" ' . (($default_value == $key) ? 'selected="selected"' : '') . ' >' . $val . '</option>';
                }
            }
            $html .= '</select>';
        } else {
            $html .= '<input replace_extras name="' . $input_name . '[' . $field['code'] . ']" type="text" class="form-control ' . $required . ' ' . $date_field . '" validate="' . $field['field_type'] . '" value="' . $value . '"  ' . (($this->isReadOnly($field['code'])) ? 'disabled="disabled"' : '') . ' />';
        }

        if (!$has_event) {
            $html = str_replace('replace_extras', '', $html);
        }
        return $html;
    }

    public function renderAttributeFieldHtml($field, $input_name = 'product', $has_event = false) {
        $html = '';
        $required = (($field['is_required']) ? 'required' : '');

        $date_field = (($field['input_type'] == 'date') ? 'has_datepicker' : '');
        $value = $field['value'];
        if ($field['input_type'] == 'textarea') {
            $maxlength = '';
            if ($field['code'] == 'short_description') {
                $maxlength .= 'maxlength="' . \Knowband\Marketplace\Block\Product\Section\Section::getShortDescriptionLength() . '"';
            } else if ($field['code'] == 'description') {
                $maxlength .= 'maxlength="' . \Knowband\Marketplace\Block\Product\Section\Section::getLongDescriptionLength() . '"';
            }
            $html .= '<textarea replace_extras ' . $maxlength . ' rows="5" name="' . $input_name . '[' . $field['code'] . ']"  class="form-control ' . $required . '">' . $value . '</textarea>';
        } else if ($field['input_type'] == 'select' || $field['input_type'] == 'multiselect') {
            $html .= '<select replace_extras name="' . $input_name . '[' . $field['code'] . ']" class="form-control ' . $required . '" ' . (($this->isReadOnly($field['code'])) ? 'disabled="disabled"' : '') . ' >';
            $list = $field['source'];
            foreach ($list as $val) {
                $html .= '<option value="' . $val['value'] . '" ' . (($field['value'] == $val['value']) ? 'selected="selected"' : '') . ' >' . $val['label'] . '</option>';
            }
            $html .= '</select>';
        } else {
            if(is_array($value)){
                $value = implode(",", $value);
            }
            $html .= '<input replace_extras name="' . $input_name . '[' . $field['code'] . ']" type="text" class="form-control ' . $required . ' ' . $date_field . '" value="' . $value . '"  ' . (($this->isReadOnly($field['code'])) ? 'disabled="disabled"' : '') . '/>';
        }
        if (!$has_event) {
            $html = str_replace('replace_extras', '', $html);
        }
        return $html;
    }
    
    public function getFrontUrl($controller, $action = '', $params = []){
        
        return $this->_objectManager->get("\Knowband\Marketplace\Helper\Data")->getFrontUrl($controller, $action, $params);
    }
    
    public function getFieldDetail($code) {
        return $this->_objectManager->get("\Knowband\Marketplace\Helper\Fields")->getFieldDetail($code);
    }
    
    public function getFieldKey($custom_code) {
        return $this->_objectManager->get("\Knowband\Marketplace\Helper\Fields")->getFieldKey($custom_code);
    }
    
    public function getFormBlocks(){
        return $this->_objectManager->get("\Knowband\Marketplace\Helper\Fields")->getFormBlocks();
    }


    public function jsonEncode($values){
        return $this->_objectManager->get("\Magento\Framework\Json\Helper\Data")->jsonEncode($values);
    }
}
