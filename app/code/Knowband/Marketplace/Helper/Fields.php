<?php

/**
 * Knowband_Marketplace
 *
 * @category    Knowband
 * @package     Knowband_Marketplace
 * @author      Knowband Team <support@knowband.com.com>
 * @copyright   Knowband (http://wwww.knowband.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Knowband\Marketplace\Helper;
class Fields extends \Magento\Framework\App\Helper\AbstractHelper
{
    
    private $_form_block = ['general','prices', 'inventory', 'meta'];
	
	//custom_code => system_code
	private $_custom_field_map = [
	    'name'		=> 'name',
	    'sku'		=> 'sku',
	    'status'		=> 'status',
	    'weight'		=> 'weight',
	    'visibility'	=> 'visibility',
	    'description'	=> 'description',
	    'short_description'	=> 'short_description',
	    'news_from_date'	=> 'news_from_date',
	    'news_to_date'	=> 'news_to_date',
	    'url_key'		=> 'url_key',
	    'price'		=> 'price',
	    'cost'		=> 'cost',
            'price_view'        => 'price_view',
	    'special_price'	=> 'special_price',
	    'special_from_date'	=> 'special_from_date',
	    'special_to_date'	=> 'special_to_date',
	    'meta_title'	=> 'meta_title',
	    'meta_keyword'	=> 'meta_keyword',
	    'meta_description'	=> 'meta_description',
	    'base_image'	=> 'image',
	    'small_image'	=> 'small_image',
	    'thumbnail'		=> 'thumbnail',
	    'gift_message_available'		=> 'gift_message_available',
	    'gift_wrapping_available'		=> 'gift_wrapping_available',
	    'gift_wrapping_price'		=> 'gift_wrapping_price',
	    'options_container'	=> 'options_container' //catalog/entity_product_attribute_design_options_container
	];
	
	private $_group_skipped = ['general', 'prices', 'meta information', 'images', 'design', 'gift options', 'recurring profile'];
	
	private $_mediaAttribute = [];

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Registry $registry,
        \Knowband\Marketplace\Helper\Log $mpLogHelper
    )
    {
        $this->_storeManager = $storeManager;
        $this->_request = $request;
        $this->_registry = $registry;
        $this->_objectManager = $objectManager;
        $this->mp_logHelper = $mpLogHelper;
        parent::__construct($context);
    }
    
    public function excludeGroup($group_name) {
        if (in_array(strtolower($group_name), $this->_group_skipped)) {
            return true;
        }
        return false;
    }

    public function getFormBlocks() {
        return $this->_form_block;
    }

    public function getFieldKey($custom_code) {
        return $this->_custom_field_map[$custom_code];
    }

    public function getMandatoryFields($set_id) {
        $fields = $this->fetchAllFields($set_id);
        return $fields;
    }
    
    public function getFrontUrl($controller, $action = null, $params = []) {
        $url = 'marketplace/' . $controller;
        if (!empty($action)) {
            $url .= '/' . $action;
        }
        return $this->_getUrl($url, $params);
    }

    public function fetchAllFields($set_id) {
        $fields = [];
        try {
            $seller_info = $this->_registry->registry('vssmp_seller_info');
            
            $configurable = $this->_objectManager->get('\Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Attribute')
                    ->getUsedAttributes($set_id);

            $product = $this->_registry->registry('vssmp_current_product');
            if($product)
            if (!($setId = $product->getAttributeSetId())) {
                $setId = $this->_request->getParam('set', null);
            }
            
            $groupCollection = $this->_objectManager->get("\Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory")->create()
                    ->setAttributeSetFilter($setId)
                    ->setSortOrder()
                    ->load();

            $seller_approved = $this->_objectManager->create('Knowband\Marketplace\Model\Seller')->load($seller_info['entity_id'], 'seller_id');
            foreach ($groupCollection as $group) {
                $attributes = $product->getAttributes($group->getId(), true);
                foreach ($attributes as $key => $child) {
                    if (!$child->getIsVisible()) {
                        unset($attributes[$key]);
                    }
                    if (!$seller_approved->getSellerApproved() && $child->getAttributeCode() == 'status') {
                        $default_value = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED;
                    } else {
                        $default_value = $child->getDefaultValue();
                    }
                    $validation_type = $child->getBackendType();
                    if ($child->getBackendType() == 'date' || $child->getBackendType() == 'datetime') {
                        $validation_type = 'datepicker';
                    }
                    if ($child->getFrontendInput() == 'textarea') {
                        $validation_type = 'varchar';
                    }
                    $fields[$child->getAttributeCode()] = [
                        'code' => $child->getAttributeCode(),
                        'id' => $child->getAttributeId(),
                        'label' => $child->getFrontendLabel(),
                        'is_required' => $child->getIsRequired(),
                        'is_visible' => $child->getIsVisible(),
                        'apply_to' => (($child->getApplyTo() != '' && !is_array($child->getApplyTo())) ? explode(',', $child->getApplyTo()) : []),
                        'is_configurable' => (int) in_array($child->getAttributeId(), $configurable),
                        'default_value' => $default_value,
                        'field_type' => $validation_type,
                        'field_input' => ($child->getFrontendInput() == 'price') ? 'text' : $child->getFrontendInput(),
                        'source_model' => $child->getSourceModel()
                    ];
                }
            }
        } catch (\Exception $ex) {
            $this->mp_logHelper->createFileAndWriteLogData(
                    \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Helper Fields::fetchAllFields()', $ex->getMessage()
            );
        }
        return $fields;
    }

    public function getFieldDetail($code) {
        $fields = $this->_registry->registry('current_product_fields');
        if (isset($fields[$code])) {
            return $fields[$code];
        }
        return false;
    }

    public function getDropDownData($source_model) {
        try {
            if($source_model == 'Magento\Bundle\Model\Product\Attribute\Source\Price\View'){
                $result = [];
                $options = $this->_objectManager->get($source_model)->getAllOptions();
                foreach($options as $option){
                    $result[$option['value']] = $option['label'];
                }
                return $result;
            } else {
                return $this->_objectManager->get($source_model)->getOptionArray();
            }
        } catch (\Exception $ex) {
            $this->mp_logHelper->createFileAndWriteLogData(
                    \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Helper Fields::getDropDownData()', $ex->getMessage()
            );
            return [];
        }
    }

//    public function customFieldMap() {
//        
//    }

}
