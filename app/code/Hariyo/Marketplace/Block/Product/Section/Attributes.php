<?php

namespace Hariyo\Marketplace\Block\Product\Section;

class Attributes extends \Hariyo\Marketplace\Block\Product\Base 
{    
    private $_attributes_fields = [];
    private $_attributes_group_name = '';
    
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setsFactory,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Magento\Framework\Registry $registry,
            \Magento\Framework\App\Response\Http $response,
            \Hariyo\Marketplace\Helper\Fields $mpFieldHelper
    ) {
        $this->_coreRegistry = $registry;
        $this->_setsFactory = $setsFactory;
        $this->_objectManager = $objectManager;
        $this->_response = $response;
        $this->mp_fieldHelper = $mpFieldHelper;
        parent::__construct($context, $setsFactory, $objectManager, $registry);
        $this->setTemplate('product/section/attributes.phtml');
        $this->_init();
    }
    
    private function _init() {
        $product = $this->getProduct();
        if (!$product || !($setId = $product->getAttributeSetId())) {
            $setId = null;
        }
        if ($setId) {
            $groupCollection = $this->_objectManager->get("\Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory")->create()
                    ->setAttributeSetFilter($setId)
                    ->setSortOrder()
                    ->load();
            foreach ($groupCollection as $group) {
                if ($this->mp_fieldHelper->excludeGroup($group->getAttributeGroupName())) {
                    continue;
                }
                $this->_attributes_group_name = $group->getAttributeGroupName();
                $attributes = $product->getAttributes($group->getId(), true);
                if (count($attributes) == 0) {
                    continue;
                }

                foreach ($attributes as $key => $attribute) {
                    if ($attribute->getIsVisible()) {
                        if(in_array($attribute->getAttributeCode(), $this->getCommonAttributes())){
                            continue;
                        }
                        $apply_to = $attribute->getApplyTo();
                        if (!empty($apply_to) && !in_array($product->getTypeId(), $attribute->getApplyTo())) {
                            continue;
                        }
                        $inputType = $attribute->getFrontend()->getInputType();
                        $source = '';
                        if ($inputType == 'select') {
                            $source = $attribute->getSource()->getAllOptions(true, true);
                        } else if ($inputType == 'multiselect') {
                            $source = $attribute->getSource()->getAllOptions(false, true);
                        }

                        $colmn = str_replace('_', ' ', $attribute->getAttributeCode());
                        $colmn = 'get' . str_replace(' ', '', ucwords($colmn));
                        $this->_attributes_fields[] = [
                            'code' => $attribute->getAttributeCode(),
                            'input_type' => $inputType,
                            'value' => ($product->getId()) ? $product->$colmn() : $attribute->getDefaultValue(),
                            'label' => $attribute->getFrontend()->getLabel(),
                            'is_required' => $attribute->getIsRequired(),
                            'source' => $source
                        ];
                    }
                }
            }
            unset($groupCollection);
        } else {
            $this->_response->setRedirect($this->mp_fieldHelper->getFrontUrl('product', 'new'));
        }
    }

    public function getGroupName() {
        return $this->_attributes_group_name;
    }

    public function getGroupFields() {
        return $this->_attributes_fields;
    }
    
    /**
     * Function to get the common attributes
     * 
     * @return array
     */
    public function getCommonAttributes() {
        $result = ['status', 'name', 'sku', 'price', 'quantity_and_stock_status',
            'weight', 'category_ids', 'news_from_date', 'news_to_date', 'description',
            'short_description', 'url_key', 'visibility', 'special_price',
            'special_from_date', 'special_to_date', 'meta_title', 'meta_keyword', 'meta_description'];
        return $result;
    }

}
