<?php

namespace Hariyo\Marketplace\Block\Product\Section;

class Category extends \Magento\Framework\View\Element\Template {

    private $_selected_categories = [];
    private $_allowed_categories = [];

    CONST CHILD_REL_SYMBOL = '-';
    
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Framework\Registry $registry,
            \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollection,
            \Hariyo\Marketplace\Helper\Setting $mpSettingHelper, 
            \Psr\Log\LoggerInterface $mpLogHelper, 
            \Hariyo\Marketplace\Model\Settings $mpSettingModel,
            \Hariyo\Marketplace\Model\Seller $mpSellerModel
    ) {
        $this->mp_settingsHelper = $mpSettingHelper;
        $this->mp_logHelper = $mpLogHelper;
        $this->mp_sellerModel = $mpSellerModel;
        $this->mp_settingModel = $mpSettingModel;
        $this->_coreRegistry = $registry;
        $this->_categoryCollection = $categoryCollection;
        $this->_allowed_categories = $this->_coreRegistry->registry('allowed_categories');
        parent::__construct($context);
    }

    public function setSelectedCategory($categories = [])
    {
        $this->_selected_categories = $categories;
    }
    
    public function getCategoriesDropdown() {
        $categories = [];
        $categoriesArrayCol = $this->_categoryCollection->create()
                ->addAttributeToSelect('*')
                ->addAttributeToSort('path', 'asc')
                ->addFieldToFilter('is_active', ['eq' => '1']);
        if($categoriesArrayCol->getSize()){
            foreach ($categoriesArrayCol as $category) {
                $categories[] = [
                    'label' => $category->getName(),
                    'level' => $category->getLevel(),
                    'value' => $category->getEntityId()
                ];
            }
        }
        return $categories;
    }

    public function getCategoriesDropdownHtml() {
        $html = "";
        $categories = $this->getCategoriesDropdown();
        foreach ($categories as $value) {
            foreach ($value as $key => $val) {
                if ($key == 'label') {
                    $catNameIs = $val;
                }
                if ($key == 'value') {
                    $catIdIs = $val;
                }
                if ($key == 'level') {
                    $catLevelIs = $val;
                    $b = '';
                    for ($i = 1; $i < $catLevelIs; $i++) {
                        $b = $b . self::CHILD_REL_SYMBOL;
                    }
                }
            }
            $selected = "";
            if (in_array($catIdIs, $this->_selected_categories)) {
                $selected = "selected";
            }
            if (!empty($this->_allowed_categories)) {
                $disabled = 'disabled="disabled"';
            } else {
                $disabled = "";
            }

            if (!empty($this->_allowed_categories)) {
                $disabled = 'disabled="disabled"';
                if (in_array($catIdIs, $this->_allowed_categories)) {
                    $disabled = '';
                }
            } else {
                $disabled = '';
            }
            $html .= '<option value="' . $catIdIs . '" ' . $selected . ' ' . $disabled . '>' . $b . $catNameIs . '</option>';
        }
        return $html;
    }

    public function getChildRelationSymbol()
    {
        return self::CHILD_REL_SYMBOL;
    }

    public function getIdString($arr = [])
    {
        return implode(',', $arr);
    }
    
    public function getProduct(){
        return $this->_coreRegistry->registry("vssmp_current_product");
    }

}
