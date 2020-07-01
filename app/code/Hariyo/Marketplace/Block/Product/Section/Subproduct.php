<?php

namespace Hariyo\Marketplace\Block\Product\Section;

class Subproduct extends \Hariyo\Marketplace\Block\Product\Base 
{    
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setsFactory,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Magento\Framework\Registry $registry,
            \Magento\Framework\Json\Helper\Data $jsonHelper,
            \Hariyo\Marketplace\Helper\Setting $settingHelper,
            \Hariyo\Marketplace\Model\Seller $sellerModel
    ) {
        $this->_coreRegistry = $registry;
        $this->_setsFactory = $setsFactory;
        $this->_objectManager = $objectManager;
        $this->_objectManager = $objectManager;
        $this->_jsonHelper = $jsonHelper;
        $this->mp_settingHelper = $settingHelper;
        $this->mp_sellerModel = $sellerModel;
        
        parent::__construct($context, $setsFactory, $objectManager, $registry);
        $this->setTemplate('product/section/sub_product.phtml');
    }
    
    /**
     * Retrieve attributes data in JSON format
     *
     * @return string
     */
    public function getAttributesJson() {
        $attributes = $this->getProduct()->getTypeInstance(true)
                ->getConfigurableAttributesAsArray($this->getProduct());
        if (!$attributes) {
            return '[]';
        } else {
            // Hide price if needed
            foreach ($attributes as &$attribute) {
                if (isset($attribute['values']) && is_array($attribute['values'])) {
                    foreach ($attribute['values'] as &$attributeValue) {
                        $attributeValue['pricing_value'] = '';
                        $attributeValue['is_percent'] = 0;
                    }
                }
            }
        }
        return $this->jsonHelper->jsonEncode($attributes);
    }

    /**
     * Retrieve Links in JSON format
     *
     * @return string
     */
    public function getLinksJson() {
        $products = $this->getProduct()->getTypeInstance(true)
                ->getUsedProducts(null, $this->getProduct());
        if (!$products) {
            return [];
        }
        $data = array();
        foreach ($products as $product) {
            $data[$product->getId()] = $this->jsonHelper->jsonEncode($this->getConfigurableSettings($product));
        }
        return $data;
    }

    /**
     * Retrieve configurable settings
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getConfigurableSettings($product) {
        $data = [];
        $attributes = $this->getProduct()->getTypeInstance(true)
                ->getUsedProductAttributes($this->getProduct());
        foreach ($attributes as $attribute) {
            $data[] = [
                'attribute_id' => $attribute->getId(),
                'label' => $product->getAttributeText($attribute->getAttributeCode()),
                'value_index' => $product->getData($attribute->getAttributeCode())
            ];
        }
        return $data;
    }

    public function getSelectedProducts() {
        return $this->getProduct()->getTypeInstance(true)->getUsedProductIds($this->getProduct());
    }

    public function getNewEmptyProductUrl() {
        return $this->getUrl(
                        '*/*/new', [
                    'process_step' => 3,
                    'set' => $this->getProduct()->getAttributeSetId(),
                    'type' => \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE,
                    'required' => $this->_getRequiredAttributesIds(),
                    'popup' => 1
                        ]
        );
    }

    protected function _getRequiredAttributesIds() {
        $attributesIds = [];
        $configurableAttributes = $this->getProduct()
                        ->getTypeInstance(true)->getConfigurableAttributes($this->getProduct());
        foreach ($configurableAttributes as $attribute) {
            $attributesIds[] = $attribute->getProductAttribute()->getId();
        }
        return implode(',', $attributesIds);
    }

    public function createSimpleButton() {
        $sellerInfo = $this->_coreRegistry->registry("vssmp_seller_info");
        $seller_collection = $this->mp_sellerModel->load($sellerInfo['entity_id'], 'seller_id');
        $sellerHelper = $this->_objectManager->get("Hariyo\Marketplace\Helper\Seller");

        if ($sellerHelper->isApprovedSeller()) {
            return true;
        } else {
            $limit = $seller_collection->getProductLimit() + 1;
            if ($this->getProduct()->getId()) {
                $limit = $seller_collection->getProductLimit();
            }
            if ($limit >= $this->mp_settingHelper->getSettingByKey($sellerInfo['entity_id'], 'product_limit')) {
                return false;
            } else {
                return true;
            }
        }
    }

    public function getLimitOverMsg() {
        return __('Adding of new product limit has been over as your account is not approved or waiting for approval. To add more products, your account need to be approved.');
    }
    
    public function getSelectedGroupSubProducts(){
        return $this->_objectManager->get('\Hariyo\Marketplace\Helper\Product')->getSelectedGroupSubProducts();
    }

}
