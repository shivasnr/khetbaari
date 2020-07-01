<?php

namespace Knowband\Marketplace\Block\Product;

class ProductList extends \Knowband\Marketplace\Block\Product\Base {
    
    private $_default_sort = ['col'=> 'entity_id', 'dir' => 'DESC'];
    
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setsFactory,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Magento\Framework\Registry $registry,
            \Knowband\Marketplace\Helper\Data $mpDataHelper
    ) {
        $this->_coreRegistry = $registry;
        $this->_objectManager = $objectManager;
        $this->mp_dataHelper = $mpDataHelper;
        parent::__construct($context, $setsFactory, $objectManager, $registry);
        $this->setTemplate('product/seller_product_list.phtml');
    }
    
    public function _prepareLayout() {
        parent::_prepareLayout();
        $this->setChild(
            'attribute_selection', $this->getLayout()->createBlock('\Knowband\Marketplace\Block\Product\Attributeselection')
        );
    }

    public function getFieldId() {
        return 'vssmp_seller_product';
    }

    public function getFilters() {
        return [
            ['label' => __('Sku'), 'name' => 'sku', 'className' => 'custom-input-field', 'type' => 'text', 'values' => ''],
            ['label' => __('Product Name'), 'name' => 'name', 'className' => 'custom-input-field', 'type' => 'text', 'values' => ''],
            ['label' => __('Product Type'), 'name' => 'type_id', 'className' => 'custom-input-field', 'type' => 'select', 'values' => $this->getProductTypes()],
            ['label' => __('Attribute Set'), 'name' => 'attribute_set_id', 'className' => 'custom-input-field', 'type' => 'select', 'values' => $this->getAttributeSet()],
            ['label' => __('Visibility'), 'name' => 'visibility', 'className' => 'custom-input-field', 'type' => 'select', 'values' => \Magento\Catalog\Model\Product\Visibility::getOptionArray()]
        ];
    }

    public function getColumns() {
        return [
            ['label' => '', 'name' => '', 'className' => 'vssmp-txt-cntr', 'targets' => 0, 'width' => 40, 'orderable' => 'false', 'searchable' => false, 'render' => 'function ( data, type, full, meta ) {createParentCheckbox()}'],
            ['label' => __('Id'), 'name' => 'entity_id', 'className' => 'vssmp-txt-ryt', 'targets' => 1, 'width' => 40, 'orderable' => 'true'],
            ['label' => __('Product Name'), 'name' => 'name', 'className' => '', 'targets' => 2, 'width' => 250, 'orderable' => 'true'],
            ['label' => __('Sku'), 'name' => 'sku', 'className' => '', 'targets' => 3, 'width' => 60, 'orderable' => 'true'],
            ['label' => __('Price'), 'name' => 'price', 'className' => 'vssmp-txt-ryt', 'targets' => 4, 'width' => 60, 'orderable' => 'true'],
            ['label' => __('Quantity'), 'name' => 'qty', 'className' => 'vssmp-txt-ryt', 'targets' => 5, 'width' => 80, 'orderable' => 'true'],
            ['label' => __('Type'), 'name' => 'type_id', 'className' => '', 'targets' => 6, 'width' => 80, 'orderable' => 'true'],
            ['label' => __('Status'), 'name' => 'status', 'className' => '', 'targets' => 7, 'width' => 80, 'orderable' => 'true'],
            ['label' => __('Visibility'), 'name' => 'visibility', 'className' => '', 'targets' => 8, 'width' => 80, 'orderable' => 'true']
        ];
    }

    public function getSellerInfo() {
        return $this->_coreRegistry->registry("vssmp_seller_info");
    }

    public function getListUrl() {
        $seller_info = $this->getSellerInfo();
        return $this->mp_dataHelper->getFrontUrl('product', 'getSellerProduct', ['id' => $seller_info['entity_id']]);
    }

    public function getSellerProduct() {
        $seller_info = $this->getSellerInfo();
        $post_data = $this->getRequest()->getParams();
        $col_order = $this->mp_dataHelper->getColOrder($post_data);
        $collection = $this->_objectManager->create("\Magento\Catalog\Model\Product")->getCollection()
                ->setStoreId(0)
                ->addAttributeToSelect('sku')
                ->addAttributeToSelect('name')
                ->addAttributeToSelect('attribute_set_id')
                ->addAttributeToSelect('type_id')
                ->addAttributeToSelect('price');

        $collection->joinField('s2p', $collection->getTable("vss_mp_product_to_seller"), 'seller_id', 'product_id=entity_id', ['seller_id' => (int) $seller_info['entity_id'], 'website_id' => (int) $seller_info['website_id']], 'inner');

        $collection->joinField('qty', $collection->getTable('cataloginventory_stock_item'), 'qty', 'product_id=entity_id', '{{table}}.stock_id=1', 'left');

        $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
        $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');

        $filters = $this->getFilters();
        foreach ($filters as $filter) {
            if (isset($post_data[$filter['name']]) && $post_data[$filter['name']] != '') {
                if ($filter['type'] == 'select' || $filter['type'] == 'date') {
                    $collection->addFieldToFilter($filter['name'], ['eq' => $post_data[$filter['name']]]);
                } else if ($filter['type'] == 'text') {
                    $collection->addFieldToFilter($filter['name'], ['like' => '%' . $post_data[$filter['name']] . '%']);
                }
            }
        }

        if (isset($col_order['col']) && $col_order['col'] != '') {
            $collection->addAttributeToSort($col_order['col'], $col_order['dir']);
        } else {
            $collection->addAttributeToSort($this->_default_sort['col'], $this->_default_sort['dir']);
        }

        $data = [];
        $data['collection'] = [];
        $countCollection = clone $collection;
        $data['count'] = count($countCollection->getData());
        unset($countCollection);
        $start = 0;
        $limit = $this->mp_dataHelper->getPageLength();
        if ($post_data['start'] > 0) {
            $start = (int) $post_data['start'];
        }
        $collection->getSelect()->limit($limit, $start);
        
        $product_arr = $collection->getData();
        unset($collection);
        if ($data['count'] > 0) {
            foreach ($product_arr as $product) {
                $pro = $this->_objectManager->create('\Magento\Catalog\Model\Product')->load($product['entity_id']);
                $product_name = '<a href="' . $pro->getProductUrl() . '" title="' . __('click to view product details') . '" target="_blank">' . $pro->getName() . '</a> (' . $this->_objectManager->get("\Knowband\Marketplace\Helper\Product")->getAttributeSetName($product['attribute_set_id']) . ')';
                if (!$this->_objectManager->get("\Magento\Catalog\Helper\Product")->canShow((int) $pro->getId()) || !$pro->isSalable()) {
                    $product_name = $pro->getName() . ' ' . '(' . $this->_objectManager->get("\Knowband\Marketplace\Helper\Product")->getAttributeSetName($product['attribute_set_id']) . ')';
                }

                $data['collection'][] = [
                    '<label><input class="flat-green" type="checkbox" name="' . $this->getFieldId() . '[]" value="' . $pro->getId() . '" /></label>',
                    '<a href="' . $this->mp_dataHelper->getFrontUrl('product', 'edit', ['id' => $pro->getId()]) . '" title="' . __('click to edit product') . '">' . $pro->getId() . '</a>',
                    $product_name,
                    $pro->getSku(),
                    $this->mp_dataHelper->formatCurrency($pro->getPrice()),
                    (int) $product['qty'],
                    $pro->getTypeId(),
                    $this->_objectManager->get("\Magento\Catalog\Model\Product\Attribute\Source\Status")->getOptionText($pro->getStatus()),
                    $this->_objectManager->get("\Magento\Catalog\Model\Product\Visibility")->getOptionText($pro->getVisibility())
                ];
                $pro->unsetData();
            }
        } else {
            $data['collection'] = [];
        }

        return $data;
    }

    public function getAttributeSetHtml() {
        return $this->getChildHtml('attribute_selection');
    }

    public function getAttributesUrl() {
        return $this->mp_dataHelper->getFrontUrl('product', 'GetAttributes');
    }

    public function getAttributesSetUrl() {
        return $this->mp_dataHelper->getFrontUrl('product', 'GetAttributesSet');
    }

    public function AttributeForTypes() {
        return [
            \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE
        ];
    }

    public function getPageLength() {
        return $this->mp_dataHelper->getPageLength();
    }

}
