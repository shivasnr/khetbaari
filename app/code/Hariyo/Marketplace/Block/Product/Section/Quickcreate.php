<?php

namespace Hariyo\Marketplace\Block\Product\Section;

class Quickcreate extends \Hariyo\Marketplace\Block\Product\Base {
    
    private $seller_collection = null;
    private $_form_fields = array();
    
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setsFactory,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Magento\Framework\Registry $registry,
            \Hariyo\Marketplace\Helper\Fields $mpFieldsHelper,
            \Hariyo\Marketplace\Model\Seller $mpSellerModel
    ) {
        $this->_coreRegistry = $registry;
        $this->_setsFactory = $setsFactory;
        $this->_objectManager = $objectManager;
        $this->mp_fieldsHelper = $mpFieldsHelper;
        $this->mp_sellerModel = $mpSellerModel;
        
        parent::__construct($context, $setsFactory, $objectManager, $registry);
        $this->setTemplate('product/section/quick_create.phtml');
        $sellerInfo = $this->_coreRegistry->registry('vssmp_seller_info');
        $this->seller_collection = $this->mp_sellerModel->load($sellerInfo['entity_id'], 'seller_id');

    }
    
    public function isRenderForm() {
        $yes = true;
        $sellerInfo = $this->_coreRegistry->registry('vssmp_seller_info');
        $limit = $this->seller_collection->getProductLimit() + 1;
        if ($this->getProduct()->getId()) {
            $limit = $this->seller_collection->getProductLimit();
        }
        $sellerHelper = $this->_objectManager->get("Hariyo\Marketplace\Helper\Seller");
        $settingHelper = $this->_objectManager->get("Hariyo\Marketplace\Helper\Setting");
        if (!$sellerHelper->isApprovedSeller() && ($limit >= $settingHelper->getSettingByKey($sellerInfo['entity_id'], 'product_limit'))) {
            $yes = false;
        }
        return $yes;
    }

    public function getLimitOverMsg() {
        return __('Adding of new product limit has been over as your account is not approved or waiting for approval. To add more products, your account need to be approved.');
    }

    public function isStatusReadOnly() {
        $sellerHelper = $this->_objectManager->get("Hariyo\Marketplace\Helper\Seller");
        return (!$sellerHelper->isApprovedSeller() || !$sellerHelper->isEnabledSeller());
    }

    public function getFormFields() {
        $attributesConfig = [
            'additional' => ['name', 'sku', 'visibility', 'status']
        ];

        $availableTypes = ['text', 'select', 'multiselect', 'textarea', 'price', 'weight'];

        $attributes = $this->_objectManager->get("Magento\Catalog\Model\Product")
                ->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
                ->setAttributeSetId($this->getProduct()->getAttributeSetId())
                ->getAttributes();

        /* Standart attributes */
        foreach ($attributes as $attribute) {
            if (($attribute->getIsRequired() && $attribute->getApplyTo()
                    // If not applied to configurable
                    && !in_array(\Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE, $attribute->getApplyTo())
                    // If not used in configurable
                    && !in_array($attribute->getId(), $this->getProduct()->getTypeInstance(true)->getUsedProductAttributeIds($this->getProduct()))
                    )
                    // Or in additional
                    || in_array($attribute->getAttributeCode(), $attributesConfig['additional'])
            ) {
                $inputType = $attribute->getFrontend()->getInputType();
                if (!in_array($inputType, $availableTypes)) {
                    continue;
                }

                if ($inputType == 'weight') {
                    $inputType = 'text';
                }
                $default_value = $attribute->getDefaultValue();
                if ($attribute->getAttributeCode() == 'status') {
                    
                    if ($this->isStatusReadOnly()) {
                        $default_value = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED;
                    }
                }

                $this->_form_fields[] = [
                    'code' => $attribute->getAttributeCode(),
                    'label' => $attribute->getFrontend()->getLabel(),
                    'is_required' => $attribute->getIsRequired(),
                    'input_type' => $inputType,
                    'is_visible' => $attribute->getIsVisible(),
                    'default_value' => $default_value,
                    'field_type' => ($attribute->getBackendType() == 'date' || $attribute->getBackendType() == 'datetime') ? 'datepicker' : $attribute->getBackendType(),
                    'field_input' => $attribute->getFrontendInput(),
                    'source_model' => $attribute->getSourceModel(),
                    'values' => ($inputType == 'select' || $inputType == 'multiselect') ? $attribute->getFrontend()->getSelectOptions() : ''
                ];
            }
        }

        /* Configurable attributes */
        $usedAttributes = $this->getProduct()->getTypeInstance(true)->getUsedProductAttributes($this->getProduct());
        foreach ($usedAttributes as $attribute) {
            $this->_form_fields[] = [
                'code' => '[' . $attribute->getAttributeCode() . ']',
                'label' => $attribute->getFrontend()->getLabel(),
                'is_required' => $attribute->getIsRequired(),
                'input_type' => $attribute->getFrontend()->getInputType(),
                'is_visible' => $attribute->getIsVisible(),
                'default_value' => $attribute->getDefaultValue(),
                'field_type' => ($attribute->getBackendType() == 'date' || $attribute->getBackendType() == 'datetime') ? 'datepicker' : $attribute->getBackendType(),
                'field_input' => $attribute->getFrontendInput(),
                'source_model' => $attribute->getSourceModel(),
                'values' => $attribute->getSource()->getAllOptions(true, true)
            ];

            $this->_form_fields[] = [
                'code' => '[pricing][' . $attribute->getAttributeCode() . '][value]',
                'label' => '',
                'is_required' => false,
                'input_type' => 'hidden',
                'is_visible' => false,
                'default_value' => '',
                'field_type' => 'decimal',
                'field_input' => 'hidden'
            ];

            $this->_form_fields[] = [
                'code' => '[pricing][' . $attribute->getAttributeCode() . '][is_percent]',
                'label' => '',
                'is_required' => false,
                'input_type' => 'hidden',
                'is_visible' => false,
                'default_value' => 0,
                'field_type' => 'decimal',
                'field_input' => 'hidden'
            ];
        }

        /* Inventory Data */
        $this->_form_fields[] = [
            'code' => '[stock_data][qty]',
            'label' => __('Qty'),
            'is_required' => true,
            'input_type' => 'text',
            'is_visible' => true,
            'default_value' => 0,
            'field_type' => 'int',
            'field_input' => 'text'
        ];
        $this->_form_fields[] = [
            'code' => '[stock_data][is_in_stock]',
            'label' => __('Stock Availability'),
            'is_required' => true,
            'input_type' => 'select',
            'is_visible' => true,
            'default_value' => 1,
            'field_type' => 'int',
            'field_input' => 'select',
            'source_model' => '',
            'values' => [
                ['value' => 1, 'label' => __('In Stock')],
                ['value' => 0, 'label' => __('Out of Stock')]
            ]
        ];

        $stockHiddenFields = [
            'use_config_min_qty' => 1,
            'use_config_min_sale_qty' => 1,
            'use_config_max_sale_qty' => 1,
            'use_config_backorders' => 1,
            'use_config_notify_stock_qty' => 1,
            'is_qty_decimal' => 0
        ];

        foreach ($stockHiddenFields as $fieldName => $fieldValue) {
            $this->_form_fields[] = [
                'code' => '[stock_data][' . $fieldName . ']',
                'label' => '',
                'is_required' => false,
                'input_type' => 'hidden',
                'is_visible' => false,
                'default_value' => $fieldValue,
                'field_type' => 'int',
                'field_input' => 'hidden',
                'source_model' => ''
            ];
        }

        return $this->_form_fields;
    }

}
