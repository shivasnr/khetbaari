<?php

/**
 * Hariyo_Marketplace
 *
 * @category    Hariyo
 * @package     Hariyo_Marketplace
 * @author      Chet B. Sunar Team <shivasnr41@gmail.com>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Hariyo\Marketplace\Helper;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory;
class Product extends \Magento\Framework\App\Helper\AbstractHelper
{
    
    CONST USE_CONFIG = 2;
    CONST CALCULATION_ALLOWED = 1;
    CONST CALCULATION_NOT_ALLOWED = 0;
    CONST ARRAY_CHUNK = 500;
    CONST CAT_MAP = 1;
    CONST CAT_UNMAP = 2;
    
    protected $mp_storeManager;
    protected $mp_scopeConfig;
    protected $mp_request;
    protected $rulesFactory;
    protected $mp_objectManager;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\ResourceModel\Group\Collection $customerGroup,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configResource,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Sales\Model\Order $salesOrderModel,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $category,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product $productModel,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Catalog\Model\Product\Action $productActionModel,
        \Hariyo\Marketplace\Model\Settings $mpSettingsModel,
        \Hariyo\Marketplace\Model\Product $mpProductToSellerModel,
        \Hariyo\Marketplace\Model\Statusaction $mpStatusActionModel,
        \Hariyo\Marketplace\Model\Categorymapping $mpCategorymappingModel,
        \Hariyo\Marketplace\Model\Orderitem $mpOrderItemModel,
        \Hariyo\Marketplace\Model\Earnings $mpEarningModel,
        \Hariyo\Marketplace\Helper\Uploader $mpUploaderHelper
    )
    {
        $this->mp_storeManager = $storeManager;
        $this->mp_scopeConfig = $context->getScopeConfig();
        $this->mp_request = $context->getRequest();
        $this->mp_resource = $configResource;
        $this->mp_objectManager = $objectManager;
        $this->date = $date;
        $this->_registry = $registry;
        $this->_orderModel = $salesOrderModel;
        $this->mp_customerGroup = $customerGroup;
        $this->_categoryFactory = $category;
        $this->_productFactory = $productCollectionFactory;
        $this->_productModel = $productModel;
        $this->_productActionModel = $productActionModel;
        $this->mp_settingsModel = $mpSettingsModel;
        $this->mp_statusActionModel = $mpStatusActionModel;
        $this->mp_productSellerModel = $mpProductToSellerModel;
        $this->mp_categoryMappingModel = $mpCategorymappingModel;
        $this->mp_orderItemModel = $mpOrderItemModel;
        $this->mp_earningModel = $mpEarningModel;
        $this->mp_uploaderHelper = $mpUploaderHelper;
        $this->_jsonHelper = $jsonHelper;
        parent::__construct($context);
    }
    
    public function getProduct() {
        return $this->_registry->registry('vssmp_current_product');
    }

    public function getSellerInfo() {
        return $this->_registry->registry('vssmp_seller_info');
    }
    
    public function getSelectedGroupSubProducts() {
        $product = $this->getProduct();
        $associatedProducts = $product->getTypeInstance()->getAssociatedProducts($product);
        $product_list = [];
        foreach ($associatedProducts as $product) {
            $product_list[$product->getId()] = [
                'id' => $product->getId(),
                'qty' => (int) $product->getQty()
            ];
        }
        return $product_list;
    }
    
    public function getAllowedTypes() {
        $allowProductTypes = [];
        $allowProductTypes = ['simple', 'downloadable', 'virtual'];
        return $allowProductTypes;
    }

    public function getSelectedRelatedProducts() {
        $products = [];
        $saved_related = $this->getProduct()->getRelatedProducts();
        if (!empty($saved_related)) {
            foreach ($saved_related as $related) {
                $products[] = $related->getEntityId();
            }
        }
        return $products;
    }

    public function isSellerProduct($product_id, $seller_id) {
        $is_seller_product = false;
        $product = $this->_productFactory->create();
        $product->getSelect()->join(['s2p' => $product->getTable('vss_mp_product_to_seller')], 'e.entity_id = s2p.product_id');
        $product->getSelect()->where('e.entity_id=' . (int) $product_id . ' and s2p.seller_id=' . (int) $seller_id);
        if ($product->getSize() > 0) {
            $is_seller_product = true;
        }
        unset($product);
        return $is_seller_product;
    }
    
    public function getRelatedProductList($post_data, $col_order = [], $list_params = []) {
        $data = [];
        $product = $this->getProduct();
        $seller_info = $this->getSellerInfo();
        $cat_prod_link = $this->mp_objectManager->create('Magento\Catalog\Model\Product\Link');
        $collection = $cat_prod_link->useRelatedLinks()
                ->getProductCollection()
                ->setProduct($product)
                ->addAttributeToSelect('*');

        $collection->joinField('s2p', $collection->getTable('vss_mp_product_to_seller'), 'seller_id', 'product_id=entity_id', ['seller_id' => (int) $seller_info['entity_id'], 'website_id' => (int) $seller_info['website_id']], 'inner');

        if ($post_data['attr_set'] != '') {
            $collection->addFieldToFilter('attribute_set_id', $post_data['attr_set']);
        }
        

        $collection->addFieldToFilter('type_id', $this->getAllowedTypes())
                ->addFilterByRequiredOptions()
                ->joinAttribute('name', 'catalog_product/name', 'entity_id', null, 'inner');

        $collection->joinTable($collection->getTable('cataloginventory_stock_item'), 'product_id=entity_id', ['stock_status' => 'is_in_stock'])
                    ->addAttributeToSelect('stock_status');
        if ($post_data['inv_status'] != '') {
            $collection->addFieldToFilter('stock_status', $post_data['inv_status']);
        }

        if ($post_data['name'] != '') {
            $collection->addFieldToFilter('name', ['like' => '%' . $post_data['name'] . '%']);
        }

        if ($post_data['sku'] != '') {
            $collection->addFieldToFilter('sku', ['like' => '%' . $post_data['sku'] . '%']);
        }

        $collection->addFieldToFilter('entity_id', ['neq' => $product->getId()]);
        if ($post_data['product_id'] != '') {
            $collection->addFieldToFilter('entity_id', ['eq' => $post_data['product_id']]);
        }

        $productids = $this->getSelectedRelatedProducts();
        if ($post_data['rel_load_first_time'] != "false") {
            if (count($productids) > 0) {
                $collection->addFieldToFilter('entity_id', ['in' => $productids]);
            } else {
                $collection->addFieldToFilter('entity_id', ['nin' => [0]]);
            }
        }

        if (isset($col_order['col']) && $col_order['col'] != '') {
            $collection->addAttributeToSort($col_order['col'], $col_order['dir']);
        }

        $countCollection = clone $collection;
        $data['count'] = count($countCollection->getData());

        $start = 0;
        $limit = \Hariyo\Marketplace\Helper\Data::PAGELIMIT;
        if ($post_data['start'] > 0) {
            $start = (int) $post_data['start'];
        }

        $collection->getSelect()->limit($limit, $start);

        $data['collection'] = $collection;
        unset($collection);
        return $data;
    }
    
    public function getListForBundleProduct($post_data, $col_order = [], $list_params = []) {
        $data = [];
        $product = $this->getProduct();
        $allowProductTypes = $this->mp_objectManager->get('\Magento\Bundle\Helper\Data')->getAllowedSelectionTypes();
        $seller_info = $this->getSellerInfo();
        $selected_products = $this->mp_request->getPost('selected_products',explode(',', $this->mp_request->getParam('productss')));
        $collection = $this->_productModel->getCollection()
                ->setOrder(
                    'id'
                )->addAttributeToSelect(
                    'name'
                )->addAttributeToSelect(
                    'sku'
                )->addAttributeToSelect(
                    'price'
                )->addAttributeToSelect(
                    'attribute_set_id'
                )->addAttributeToFilter(
                    'entity_id',
                    ['nin' => $selected_products]
                )->addAttributeToFilter(
                    'type_id',
                    ['in' => $allowProductTypes]
                )->addAttributeToFilter(
                    'status', 
                    \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
                )->addFilterByRequiredOptions()->addStoreFilter(
                    \Magento\Store\Model\Store::DEFAULT_STORE_ID
                );
        
        $collection->joinField('s2p', $collection->getTable('vss_mp_product_to_seller'), 'seller_id', 'product_id=entity_id', ['seller_id' => (int) $seller_info['entity_id'], 'website_id' => (int) $seller_info['website_id']], 'inner');
        $collection->addFieldToFilter('entity_id', ['neq' => $product->getId()]);
        if ($post_data['product_id'] != '') {
            $collection->addFieldToFilter('entity_id', ['eq' => $post_data['product_id']]);
        }
        if (isset($post_data['selected_product_ids']) && trim($post_data['selected_product_ids'], ',') != '') {
            $collection->addIdFilter(explode(',', $post_data['selected_product_ids']), true);
        }

        if ($post_data['attr_set'] != '') {
            $collection->addFieldToFilter('attribute_set_id', $post_data['attr_set']);
        }

        $collection->joinTable($collection->getTable('cataloginventory_stock_item'), 'product_id=entity_id', ['stock_status' => 'is_in_stock'])
                    ->addAttributeToSelect('stock_status');
        if ($post_data['inv_status'] != '') {
            $collection->addFieldToFilter('stock_status', $post_data['inv_status']);
        }

        if ($post_data['name'] != '') {
            $collection->addFieldToFilter('name', ['like' => '%' . $post_data['name'] . '%']);
        }

        if ($post_data['sku'] != '') {
            $collection->addFieldToFilter('sku', ['like' => '%' . $post_data['sku'] . '%']);
        }

        if (isset($col_order['col']) && $col_order['col'] != '') {
            $collection->addAttributeToSort($col_order['col'], $col_order['dir']);
        }
        $countCollection = clone $collection;
        $data['count'] = count($countCollection->getData());

        $start = 0;
        $limit = \Hariyo\Marketplace\Helper\Data::PAGELIMIT;
        if ($post_data['start'] > 0) {
            $start = (int) $post_data['start'];
        }

        $collection->getSelect()->limit($limit, $start);

        $data['collection'] = $collection;
        return $data;
    }

    public function getListForGroupProduct($post_data, $col_order = [], $list_params = []) {
        $data = [];
        $product = $this->getProduct();
        $allowProductTypes = $this->mp_objectManager->get('\Magento\Bundle\Helper\Data')->getAllowedSelectionTypes();
        $seller_info = $this->getSellerInfo();
        $selected_products = $this->mp_request->getPost('selected_products',explode(',', $this->mp_request->getParam('productss')));
        $collection = $this->_productModel->getCollection()
		    ->setOrder('id')
		    ->addAttributeToSelect('*')
		    ->addFilterByRequiredOptions()
		    ->addAttributeToFilter('type_id', $this->getAllowedTypes())
		    ->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);

        $collection->joinField('s2p', $collection->getTable('vss_mp_product_to_seller'), 'seller_id', 'product_id=entity_id', ['seller_id' => (int) $seller_info['entity_id'], 'website_id' => (int) $seller_info['website_id']], 'inner');
        $collection->addFieldToFilter('entity_id', ['neq' => $product->getId()]);
        if ($post_data['product_id'] != '') {
            $collection->addFieldToFilter('entity_id', ['eq' => $post_data['product_id']]);
        }

        if ($post_data['attr_set'] != '') {
            $collection->addFieldToFilter('attribute_set_id', $post_data['attr_set']);
        }

        $collection->joinTable($collection->getTable('cataloginventory_stock_item'), 'product_id=entity_id', ['stock_status' => 'is_in_stock'])
                    ->addAttributeToSelect('stock_status');
        if ($post_data['inv_status'] != '') {
            $collection->addFieldToFilter('stock_status', $post_data['inv_status']);
        }

        if ($post_data['name'] != '') {
            $collection->addFieldToFilter('name', ['like' => '%' . $post_data['name'] . '%']);
        }

        if ($post_data['sku'] != '') {
            $collection->addFieldToFilter('sku', ['like' => '%' . $post_data['sku'] . '%']);
        }

        if ($post_data['sub_load_first_time'] != "false") {
            $sub_products = $this->getSelectedGroupSubProducts();
            $productids = [];
            foreach ($sub_products as $pro) {
                $productids[] = $pro['id'];
            }
            if (count($productids) > 0) {
                $collection->addFieldToFilter('entity_id', ['in' => $productids]);
            } else {
                $collection->addFieldToFilter('entity_id', ['nin' => [0]]);
            }
        } else {
            if (!isset($post_data['checked_products'])) {
                
            }
        }

        if (isset($col_order['col']) && $col_order['col'] != '') {
            $collection->addAttributeToSort($col_order['col'], $col_order['dir']);
        }

        $countCollection = clone $collection;
        $data['count'] = count($countCollection->getData());

        $start = 0;
        $limit = \Hariyo\Marketplace\Helper\Data::PAGELIMIT;
        if ($post_data['start'] > 0) {
            $start = (int) $post_data['start'];
        }

        $collection->getSelect()->limit($limit, $start);
        
        $data['collection'] = $collection;
        return $data;
    }

    public function getAttributeSetName($set_id) {
        
        $attributeSetModel = $this->mp_objectManager->get("\Magento\Eav\Model\Entity\Attribute\Set");
        $attributeSetModel->load($set_id);
        $attr_name = $attributeSetModel->getAttributeSetName();
        $attributeSetModel->unsetData();
        return $attr_name;
    }
    
    public function getStockStatusTxt($stock_status) {
        return $stock_status ? __('In Stock') : __('Out of Stock');
    }
    
    public function processDataBeforeSave($data) {
        
        $product = $temp = $data['product'];
        unset($product['rel_products']);
        unset($product['sub_pro_filter_id']);
        unset($product['sub_pro_filter_sku']);
        unset($product['sub_pro_filter_name']);
        unset($product['sub_pro_filter_inv_status']);
        unset($product['sub_pro_filter_attr_set']);

        //disabled product, if seller not approved
        $sellerHelper = $this->mp_objectManager->get("\Hariyo\Marketplace\Helper\Seller");
        if (!$sellerHelper->isApprovedSeller() || !$sellerHelper->isEnabledSeller()) {
            $product['status'] = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED;
        }

        //$product['category_ids'] = $data['category_ids']; //Categories
        //$product['weight'] = 1; //Weight
        $product['country_of_manufacture'] = ''; //Country of Manufacturer
        $product['msrp_enabled'] = 2; //Apply Map
        $product['msrp_display_actual_price_type'] = 4; //Display actual price
        $product['msrp'] = ''; //Manufacturer's Suggested Retail Price
        $product['tax_class_id'] = 0; //Taxable Goods (None)
        $product['custom_design'] = ''; //Custom Design
        $product['custom_design_from'] = ''; //Custom Design From Date
        $product['custom_design_to'] = ''; //Custom Design To Date
        $product['custom_layout_update'] = ''; //Custom Layout Update
        $product['page_layout'] = ''; //Page Layout
        $product['options_container'] = 'container1'; //Display Product Options In
        $product['use_config_gift_message_available'] = 1; //Allow Gift Messaging (Use Config)
        $product['is_recurring'] = 0; //Disable recurring profile
        //Inventory
        $product['stock_data']['enable_qty_increments'] = 0; //Enable Qty Increments (No)
        $product['stock_data']['use_config_manage_stock'] = 0; //Disable config manage stock
        if ($data['type'] == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE || $data['type'] == \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL || $data['type'] == 'downloadable') {
            $product['stock_data']['use_config_min_qty'] = 1; //Qty for Item's Status to Become Out of Stock (Use Config)
            if (!isset($product['stock_data']['is_decimal_divided']) || $product['stock_data']['is_qty_decimal'] == 0) {
                $product['stock_data']['is_decimal_divided'] = 0;
            }
            $product['stock_data']['is_decimal_divided'] = 0;
            $product['stock_data']['use_config_min_sale_qty'] = 1; //Minimum Qty Allowed in Shopping Cart (Use Config)
            $product['stock_data']['use_config_max_sale_qty'] = 1; //Maximum Qty Allowed in Shopping Cart (Use Config)
            $product['stock_data']['use_config_backorders'] = 1; //Backorders (Use Config)
            $product['stock_data']['use_config_notify_stock_qty'] = 1; //Notify for Quantity Below (Use Config)	
        }

        //Bundle Product
        if ($data['type'] == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
            $product['sku_type'] = 1; //Sku type (Fixed)
            $product['weight_type'] = 1; //Weight type (Fixed)
            $product['price_type'] = 1; //Price type (Fixed)	
        }

        //Website Ids
        $website_id = $this->mp_storeManager->getStore()->getWebsiteId();
        $product['website_ids'] = [$website_id];

        //Links - Related
        if (isset($temp['rel_products'])) {
            $product['links']['related'] = $this->processLinksBeforeSave($temp['rel_products'], $data['type'], $data['product']['sku'], 'related');
        }

        if ($data['type'] == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            $product = $this->processAssociatedBeforeSave($product);
//			}
        }
        if ($data['type'] == \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE) {
            //$temp['sub_products'] = {"2069":{"id":2069,"qty":"0"},"2071":{"id":2071,"qty":"12"}}
            $product['links']['grouped'] = $this->processLinksBeforeSave($temp['sub_products'], $data['type'], $data['product']['sku']);
            unset($product['sub_products']);
        }

        if ($data['type'] == 'downloadable' && !$this->mp_request->getParam('isAjax')) {
            $this->processDownloadableBeforeSave();
        }

        //Images
        if (!$this->mp_request->getParam('isAjax')) {
            $product = $this->processImageBeforeSave($product);
        }
        unset($product['gallery']);

        return $product;
    }
    
    public function processLinksBeforeSave($products_list_json = '', $type, $main_sku, $link_type = '') {
        $data = [];
        $position = 0;
        if ($products_list_json != '' && $products_list_json != '{}' && $products_list_json != '[]') {
            $products_list = (array) json_decode($products_list_json, true);
            if (!empty($products_list)) {
                
                //create related products links
                if($link_type == 'related'){
                    foreach ($products_list as $pro_id) {
                        $position++;
                        $linkedProduct = $this->mp_objectManager->create('\Magento\Catalog\Api\ProductRepositoryInterface')->getById($pro_id);
             
                        /** @var \Magento\Catalog\Api\Data\ProductLinkInterface $productLink */
                        $productLink = $this->mp_objectManager->create("\Magento\Catalog\Api\Data\ProductLinkInterfaceFactory")->create();

                        $productLink->setSku($main_sku)
                            ->setLinkType($link_type)
                            ->setLinkedProductSku($linkedProduct->getSku());
                        $data[] = $productLink;
                    }
                    return $data;
                }
                
                if ($type == \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE) {
                    foreach ($products_list as $pro) {
                        if(!isset($pro['id'])){
                            continue;
                        }
                        if (!isset($pro['qty'])) {
                            $pro['qty'] = 0;
                        }
                        $position++;
                        $linkedProduct = $this->mp_objectManager->create('\Magento\Catalog\Api\ProductRepositoryInterface')->getById($pro['id']);
             
                        /** @var \Magento\Catalog\Api\Data\ProductLinkInterface $productLink */
                        $productLink = $this->mp_objectManager->create("\Magento\Catalog\Api\Data\ProductLinkInterfaceFactory")->create();

                        $productLink->setSku($main_sku)
                            ->setLinkType(\Magento\GroupedProduct\Model\Product\Initialization\Helper\ProductLinks\Plugin\Grouped::TYPE_NAME)
                            ->setLinkedProductSku($linkedProduct->getSku())
                            ->setLinkedProductType($linkedProduct->getTypeId())
                            ->setPosition($position)
                            ->getExtensionAttributes()
                            ->setQty($pro['qty']);
                        $data[] = $productLink;
                    }
                } else {
                    foreach ($products_list as $pro_id) {
                        $data[$pro_id] = ['position' => 0];
                    }
                }
            }
        }
        return $data;
    }
    
    public function processAssociatedBeforeSave(&$data) {
        $product_configurable_attr_data = $this->_jsonHelper->jsonDecode($data['configurable_attributes_data']);
        $configurable_attributes_data = [];

        if (!empty($product_configurable_attr_data)) {
            $sub_products = $this->mp_request->getPost('sub_products', []);
            $attrinbute_index = 0;
            foreach ($product_configurable_attr_data as $attribute) {
                $arr = [];
                foreach ($sub_products as $sub_pro) {
                    $sub_product_attribs = $this->_jsonHelper->jsonDecode($sub_pro);
                    foreach ($sub_product_attribs as $attr1) {
                        if ($attr1['attribute_id'] == $attribute['attribute_id']) {
                            $tmp = $attr1;
                            $tmp['is_percent'] = 0;
                            $tmp['pricing_value'] = '';
                            $arr[] = $tmp;
                        }
                    }
                }
                $attribute['values'] = $arr;
                $attribute['html_id'] = 'configurable__attribute_' . $attrinbute_index;
                $configurable_attributes_data[] = $attribute;
                $attrinbute_index++;
            }
        }
        $this->mp_request->setPostValue('configurable_attributes_data', $configurable_attributes_data);
        unset($data['configurable_attributes_data']);

        $configurable_products_data = [];
        foreach ($sub_products as $key => $val) {
            $configurable_products_data[$key] = $val;
        }
        $this->mp_request->setPostValue('configurable_products_data', $configurable_products_data);
        return $data;
    }
    
    public function processDownloadableBeforeSave() {
        if ($downloadable = $this->mp_request->getParam('downloadable')) {
            $temp = $downloadable;
            $files_post = $this->mp_request->getFiles();
            //Sample
            if (isset($downloadable['sample']) && !empty($downloadable['sample'])) {
                foreach ($downloadable['sample'] as $index => $arr) {
                    $temp['sample'][$index]['file'] = $this->_jsonHelper->jsonDecode($arr['old_file']);
                    unset($temp['sample'][$index]['old_file']);
//                    $temp['sample'][$index]['old_file'] = $this->_jsonHelper->jsonDecode($arr['old_file']);
                    if (empty($arr['is_delete']) && $arr['type'] == 'file' && isset($files_post['downloadable']['sample'][$index]['name']) && !empty($files_post['downloadable']['sample'][$index]['name'])) {
                        $file_data = [
                            'name' => $files_post['downloadable']['sample'][$index]['name'],
                            'type' => $files_post['downloadable']['sample'][$index]['type'],
                            'tmp_name' => $files_post['downloadable']['sample'][$index]['tmp_name'],
                            'error' => $files_post['downloadable']['sample'][$index]['error'],
                            'size' => $files_post['downloadable']['sample'][$index]['size'],
                        ];
                        $uploaderObj = $this->mp_uploaderHelper;
                        $uploaderObj->setUploadingType('samples');
                        $uploaderObj->setAllowRenameFiles(true);
                        $uploaderObj->setFilesDispersion(true);
                        $result = $uploaderObj->save($file_data, $this->mp_objectManager->get('\Magento\Downloadable\Model\Sample')->getBaseTmpPath());
                        if ($result) {
                            $uploaded_data = ['file' => $result['file'], 'name' => $result['name'], 'size' => $result['size'], 'status' => 'new'];
                            $temp['sample'][$index]['file'] = [$uploaded_data];
                        }
                        
                    } else if ($arr['is_delete'] == '' && $arr['type'] == 'file' && $arr['old_file'] == '[]' && (!isset($files_post['downloadable']['sample'][$index]['name']) || $files_post['downloadable']['sample'][$index]['name'] == '')) {
                        unset($temp['sample'][$index]);
                    } else if ($arr['is_delete'] == '' && $arr['type'] == 'url' && $arr['sample_url'] == '') {
                        unset($temp['sample'][$index]);
                    } else if ($arr['is_delete'] == 1 && $arr['type'] == 'file' && $arr['old_file'] == '[]') {
                        unset($temp['sample'][$index]);
                    } else if ($arr['is_delete'] == 1 && $arr['type'] == 'url' && $arr['sample_url'] == '') {
                        unset($temp['sample'][$index]);
                    } else if (!isset($arr['type']) || $arr['type'] == '') {
                        unset($temp['sample'][$index]);
                    }
                }
            }

            //Links
            if (isset($downloadable['link']) && !empty($downloadable['link'])) {
                foreach ($downloadable['link'] as $index => $arr) {
                    //Links
                    $temp['link'][$index]['file'] = $this->_jsonHelper->jsonDecode($arr['old_file']);
                    $temp['link'][$index]['old_file'] = $this->_jsonHelper->jsonDecode($arr['old_file']);
                    if ($arr['is_delete'] == '' && $arr['type'] == 'file' && isset($files_post['downloadable']['link'][$index]['name']) && $files_post['downloadable']['link'][$index]['name'] != '') {
                        $file_data = [
                            'name' => $files_post['downloadable']['link'][$index]['name'],
                            'type' => $files_post['downloadable']['link'][$index]['type'],
                            'tmp_name' => $files_post['downloadable']['link'][$index]['tmp_name'],
                            'error' => $files_post['downloadable']['link'][$index]['error'],
                            'size' => $files_post['downloadable']['link'][$index]['size']
                        ];
                        $uploaderObj = $this->mp_uploaderHelper;
                        $uploaderObj->setUploadingType('links');
                        $uploaderObj->setAllowRenameFiles(true);
                        $uploaderObj->setFilesDispersion(true);
                        $result = $uploaderObj->save($file_data, $this->mp_objectManager->get('\Magento\Downloadable\Model\Sample')->getBaseTmpPath());
                        if ($result) {
                            $uploaded_data = ['file' => $result['file'], 'name' => $result['name'], 'size' => $result['size'], 'status' => 'new'];
                            $temp['link'][$index]['file'] = [$uploaded_data];
                        }
                    } else if ($arr['is_delete'] == '' && $arr['type'] == 'file' && $arr['old_file'] == '[]' && (!isset($files_post['downloadable']['link'][$index]['name']) || $files_post['downloadable']['link'][$index]['name'] == '')) {
                        unset($temp['link'][$index]);
                    } else if ($arr['is_delete'] == '' && $arr['type'] == 'url' && $arr['link_url'] == '') {
                        unset($temp['link'][$index]);
                    } else if ($arr['is_delete'] == 1 && $arr['type'] == 'file' && $arr['old_file'] == '[]') {
                        unset($temp['link'][$index]);
                    } else if ($arr['is_delete'] == 1 && $arr['type'] == 'url' && $arr['link_url'] == '') {
                        unset($temp['link'][$index]);
                    } else if (!isset($arr['type']) || $arr['type'] == '') {
                        unset($temp['link'][$index]);
                    }

                    //Link Samples
                    $temp['link'][$index]['sample']['file'] = $this->_jsonHelper->jsonDecode($arr['sample']['old_file']);
                    $temp['link'][$index]['sample']['old_file'] = $this->_jsonHelper->jsonDecode($arr['sample']['old_file']);
                    if ($arr['sample']['type'] == 'file' && isset($files_post['downloadable']['link_samples'][$index]['name']) && $files_post['downloadable']['link_samples'][$index]['name'] != '') {
                        $file_data = [
                            'name' => $files_post['downloadable']['link_samples'][$index]['name'],
                            'type' => $files_post['downloadable']['link_samples'][$index]['type'],
                            'tmp_name' => $files_post['downloadable']['link_samples'][$index]['tmp_name'],
                            'error' => $files_post['downloadable']['link_samples'][$index]['error'],
                            'size' => $files_post['downloadable']['link_samples'][$index]['size']
                        ];
                        $uploaderObj = $this->mp_uploaderHelper;
                        $uploaderObj->setUploadingType('link_samples');
                        $uploaderObj->setAllowRenameFiles(true);
                        $uploaderObj->setFilesDispersion(true);
                        $result = $uploaderObj->save($file_data, $this->mp_objectManager->get('\Magento\Downloadable\Model\Link')->getBaseSampleTmpPath());
                        if ($result) {
                            $uploaded_data = ['file' => $result['file'], 'name' => $result['name'], 'size' => $result['size'], 'status' => 'new'];
                            $temp['link'][$index]['sample']['file'] = [$uploaded_data];
                        }
                    }
                }
            }
            $this->mp_request->setPostValue('downloadable', $temp);
        }
    }
    
    private function processImageBeforeSave(&$product) {
        $tmp_arr = [];
        $image_arr = [];
        $values_arry = [];
        $imageTypes = $this->mp_uploaderHelper->getImageTypes();
        $no_image_text = $this->mp_uploaderHelper->getNoImageText();
        $post_files = $this->mp_request->getFiles();
        if (isset($product['gallery'])) {
            foreach ($product['gallery'] as $key => $gall) {
                if ($gall['delete'] == 1 && $gall['old_data'] != '[]') {
                    $old_data = $this->_jsonHelper->jsonDecode($gall['old_data']);
                    $old_data['removed'] = 1;
                    $image_arr[] = $old_data;
                    foreach ($imageTypes as $typeId => $type) {
                        if (isset($product[$typeId]) && $product[$typeId] == $key) {
                            $values_arry[$typeId] = $product[$typeId] = $no_image_text;
                        }
                    }
                } else if ($gall['delete'] == '' && $gall['old_data'] != '[]') {
                    $old_data = $this->_jsonHelper->jsonDecode($gall['old_data']);
                    $image_arr[] = $old_data;
                    foreach ($imageTypes as $typeId => $type) {
                        if (isset($product[$typeId]) && $product[$typeId] == $key) {
                            $values_arry[$typeId] = $product[$typeId] = $old_data['file'];
                        }
                    }
                } else if ($gall['delete'] == 1 && $gall['old_data'] == '[]') {
                    continue;
                } else if (isset($post_files['media_gallery'][$key]['name'])) {
                    $tmp_arr[$key] = [
                        'name' => $post_files['media_gallery'][$key]['name'],
                        'type' => $post_files['media_gallery'][$key]['type'],
                        'tmp_name' => $post_files['media_gallery'][$key]['tmp_name'],
                        'error' => $post_files['media_gallery'][$key]['error'],
                        'size' => $post_files['media_gallery'][$key]['size'],
                        'label' => "",
                        'position' => "",
                    ];
                }
            }
        }
        if (!empty($tmp_arr)) {
            foreach ($tmp_arr as $key => $img) {
                $value_selection = $no_image_text;
                $uploaderObj = $this->mp_uploaderHelper;
                $uploaderObj->setUploadingType('image');
                $uploaderObj->setAllowedExtension(['jpg', 'jpeg', 'gif', 'png']);
                $uploaderObj->addValidateCallback($this->mp_objectManager->get("Magento\Catalog\Helper\Image"), 'validateUploadFile');
                $uploaderObj->setAllowRenameFiles(true);
                $uploaderObj->setFilesDispersion(true);
                $mediaDirectory = $this->mp_objectManager->get('Magento\Framework\Filesystem')
                ->getDirectoryRead(DirectoryList::MEDIA);
                $destination_path = $mediaDirectory->getAbsolutePath($this->mp_objectManager->get('Magento\Catalog\Model\Product\Media\Config')->getBaseTmpMediaPath());
                $result = $uploaderObj->save($img,  $destination_path);
                if ($result) {
                    $value_selection = $result['file'];
                    $image_arr[] = [
                        'url' => $result['url'],
                        'file' => $result['file'],
                        'label' => $img['label'],
                        'position' => $img['position'],
                        'disabled' => 0,
                        'removed' => 0
                    ];
                }

                foreach ($imageTypes as $typeId => $type) {
                    if (isset($product[$typeId]) && $product[$typeId] == $key) {
                        $values_arry[$typeId] = $product[$typeId] = $value_selection;
                    }
                }
            }
            foreach ($imageTypes as $typeId => $type) {
                if (!isset($values_arry[$typeId])) {
                    $values_arry[$typeId] = $product[$typeId] = $no_image_text;
                }
            }
        }

        if (!empty($image_arr)) {
            $product['media_gallery']['images'] = $this->_jsonHelper->jsonEncode($image_arr);
        } else {
            $product['media_gallery']['images'] = $this->_jsonHelper->jsonEncode([]);
        }

        if (!empty($values_arry)) {
            $product['media_gallery']['values'] = $this->_jsonHelper->jsonEncode($values_arry);
        } else {
            foreach ($imageTypes as $typeId => $type) {
                $values_arry[$typeId] = $product[$typeId] = $no_image_text;
            }
            $product['media_gallery']['values'] = $this->_jsonHelper->jsonEncode($values_arry);
        }

        return $product;
    }
    
    public function statusUpdateOnProductApproval($product_id, $product_approval_type, $seller_id) {
        $action_type = \Hariyo\Marketplace\Helper\GridAction::ACTION_PRODUCT_APPROVAL;
        $action = $this->mp_statusActionModel->getCollection();
        $action->addFieldToFilter('product_id', ['eq' => $product_id]);
        $action->addFieldToFilter('seller_id', ['eq' => $seller_id]);
        $action->addFieldToFilter('action', ['eq' => $action_type]);

        if ($product_approval_type != \Hariyo\Marketplace\Helper\GridAction::APPROVED) {
            $this->updateProductStatus([$product_id], \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED);
            if ($action->getSize() == 0) {
                $insert_row = $this->mp_statusActionModel;
                $insert_row->setAction($action_type);
                $insert_row->setProductId($product_id);
                $insert_row->setSellerId($seller_id);
                $insert_row->save();
                $insert_row->unsetData();
            }
        } else if ($product_approval_type == \Hariyo\Marketplace\Helper\GridAction::APPROVED) {
            if ($action->getSize() == 1) {
                $this->updateProductStatus([$product_id], \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
            }
            $action->walk('delete');
        }
        unset($action);
    }
    
    /*
     * Function to fetch all the enabled products of any seller
     */
    public function getSellerEnabledProducts($seller_id, $website_id = -1) {
        $sellerProducts = [];
        try {

            $productCollection = $this->mp_productSellerModel->getCollection()
                    ->addFieldToFilter('seller_id', (int) $seller_id)
                    ->addFieldToFilter('approved', (int) \Hariyo\Marketplace\Helper\GridAction::APPROVED);

            if ($website_id != -1)
                $productCollection->addFieldToFilter('website_id', (int) $website_id);
            $productCollection->getSelect()
                    ->reset(\Zend_Db_Select::COLUMNS)
                    ->columns('product_id');

            $products = $productCollection->getData();
            unset($productCollection);
            foreach ($products as $pro) {
                $sellerProducts[] = $pro['product_id'];
            }
            if (empty($sellerProducts)) {
                return [0];
            }
        } catch (\Exception $ex) {
            $this->mp_logHelper->createFileAndWriteLogData(
                    \Hariyo\Marketplace\Helper\Log::INFOTYPEERROR, 'Helper Product::getSellerEnabledProducts()', $e->getMessage()
            );
        }

        return $sellerProducts;
    }

    public function getArrayInChunks($items = []) {
        $data = [];
        if (count($items) <= self::ARRAY_CHUNK) {
            $data[] = $items;
            return $data;
        } else {
            $i = 0;
            while ($i < count($items)) {
                $upto = (self::ARRAY_CHUNK - 1);
                $data[] = array_slice($items, $i, $i + $upto);
                $i = $i + $upto + 1;
            }
            for ($i = 1; $i <= count($items); $i = $i + self::ARRAY_CHUNK) {
                $data[] = array_slice($items, ($i - 1), self::ARRAY_CHUNK);
            }
            return $data;
        }
    }

    public function statusUpdateOnSellerEnable($seller_id, $seller_enable_type) {
        try {
            $action_type = \Hariyo\Marketplace\Helper\GridAction::ACTION_SELLER_ENABLE;
            $productColl = $this->mp_productSellerModel->getCollection()
                    ->addFieldToFilter('seller_id', ['eq' => $seller_id]);
            $seller_products = $productColl->getData();
            $chunk_data = $this->getArrayInChunks($seller_products);

            if (!empty($chunk_data)) {
                foreach ($chunk_data as $chunk) {
                    if ($seller_enable_type != \Hariyo\Marketplace\Helper\GridAction::ENABLED) {
                        $tmp_array = [];
                        foreach ($chunk as $productData) {
                            $tmp_array[] = $product_id = $productData['product_id'];
                            $action = $this->mp_statusActionModel->getCollection();
                            $action->addFieldToFilter('product_id', ['eq' => $product_id]);
                            $action->addFieldToFilter('seller_id', ['eq' => $seller_id]);
                            $action->addFieldToFilter('action', ['eq' => $action_type]);
                            if ($action->getSize() == 0) {
                                $insert_row = $this->mp_statusActionModel;
                                $insert_row->setAction($action_type);
                                $insert_row->setProductId($product_id);
                                $insert_row->setSellerId($seller_id);
                                $insert_row->save();
                                $insert_row->unsetData();
                            }
                            unset($action);
                        }
                        $this->updateProductStatus($tmp_array, \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED);
                    } else if ($seller_enable_type == \Hariyo\Marketplace\Helper\GridAction::ENABLED) {
                        $tmp_array = [];
                        foreach ($chunk as $productData) {
                            $product_id = $productData['product_id'];
                            $action = $this->mp_statusActionModel->getCollection();
                            $action->addFieldToFilter('product_id', ['eq' => $product_id]);
                            $action->addFieldToFilter('seller_id', ['eq' => $seller_id]);
                            $action->addFieldToFilter('action', ['eq' => $action_type]);
                            if (count($action->getData()) == 1) {
                                $tmp_array[] = $product_id;
                            }
                            $action->walk('delete');
                            unset($action);
                        }
                        $this->updateProductStatus($tmp_array, \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
                    }
                }
            }
        } catch (\Exception $ex) {
            $this->mp_logHelper->createFileAndWriteLogData(
                    \Hariyo\Marketplace\Helper\Log::INFOTYPEERROR, 'Helper Product::statusUpdateOnSellerEnable()', $e->getMessage()
            );
        }
    }

    public function updateProductStatus($productsIds, $status, $store_id = 0) {
        $this->_registry->register('donot_process_in_event', true);
        $this->mp_objectManager->create('\Magento\Catalog\Model\ResourceModel\Product\Action')->updateAttributes($productsIds, ['status' => $status], $store_id);
    }

    public function updateCatgoryMapping($categories, $mode, $level, $seller_id = 0) {
        try {
            $sellers = [];
            if ($level == 'global' && $seller_id == 0) {
                $coll = $this->mp_settingsModel->getCollection()
                        ->addFieldToFilter('use_default', ['eq' => 1])
                        ->addFieldToFilter('field_name', ['eq' => 'category_ids']);
                $coll->getSelect()->group('seller_id');

                if (!empty($coll->getData())) {
                    foreach ($coll as $val) {
                        $sellers[] = $val->getSellerId();
                    }
                }
            } else if (($level == 'seller' || $level == 'global') && $seller_id > 0) {
                $sellers[] = $seller_id;
            }

            foreach ($sellers as $seller_id) {
                if ($mode == self::CAT_UNMAP) {
                    $collection = $this->_productFactory->create();
                    $collection->joinField('category_id', $collection->getTable('catalog_category_product'), 'category_id', 'product_id = entity_id', null, 'inner');
                    $collection->addAttributeToFilter('category_id', ['in' => $categories]);
                    $collection->getSelect()->join(['s2p' => $collection->getTable('vss_mp_product_to_seller')], 'e.entity_id = s2p.product_id');
                    $collection->getSelect()->where('s2p.seller_id = ' . $seller_id);
                    $collection->getSelect()->group('e.entity_id');
                    if ($collection->getSize() > 0) {
                        foreach ($collection as $item) {
                            $product = $this->_productModel
                                            ->setStoreId(0)->load($item->getEntityId());
                            $saved_product_category = $product->getCategoryIds();
                            $category_to_be_unmap = array_intersect($categories, $saved_product_category);
                            foreach ($category_to_be_unmap as $cat) {
                                $map_col = $this->mp_categoryMappingModel->getCollection();
                                $map_col->getSelect()->where('product_id = ' . $item->getEntityId() . ' AND seller_id = ' . $seller_id . ' AND category_id = ' . $cat);
                                if (empty($map_col->getData())) {
                                    $insert_row = $this->mp_categoryMappingModel;
                                    $insert_row->addData(['product_id' => $item->getEntityId(), 'seller_id' => $seller_id, 'category_id' => $cat]);
                                    $insert_row->save();
                                    unset($insert_row);
                                }
                                unset($map_col);
                            }
                            $product->setCategoryIds(array_diff($saved_product_category, $categories));
                            $product->save();
                            $product->unsetData();
                        }
                    }
                } else if ($mode == self::CAT_MAP) {
                    $collection = $this->mp_categoryMappingModel->getCollection();
                    $collection->getSelect()->where('seller_id = ' . $seller_id);
                    if (!empty($categories)) {
                        $collection->getSelect()->where('category_id IN (' . implode(',', $categories) . ')');
                    }
                    if ($collection->getSize() > 0) {
                        foreach ($collection as $item) {
                            $product = $this->_productModel
                                            ->setStoreId(0)->load($item->getProductId());
                            $saved_product_category = $product->getCategoryIds();
                            $saved_product_category = array_unique(array_merge($saved_product_category, [$item->getCategoryId()]));
                            $product->setCategoryIds($saved_product_category);
                            $product->save();
                            $map_col = $this->mp_categoryMappingModel->getCollection();
                            $map_col->getSelect()->where('product_id = ' . $item->getProductId() . ' AND seller_id = ' . $seller_id . ' AND category_id = ' . $item->getCategoryId());
                            $map_col->walk('delete');
                            unset($map_col);
                            $product->unsetData();
                        }
                    }
                    unset($collection);
                }
            }
        } catch (\Exception $ex) {
            $this->mp_logHelper->createFileAndWriteLogData(
                    \Hariyo\Marketplace\Helper\Log::INFOTYPEERROR, 'Helper Product::updateCatgoryMapping()', $e->getMessage()
            );
        }
    }

    public function statusUpdateOnSellerApproval($seller_id, $seller_approval_type) {
        try {
            $action_type = \Hariyo\Marketplace\Helper\GridAction::ACTION_SELLER_APPROVAL;
            $productColl = $this->mp_productSellerModel->getCollection()
                    ->addFieldToFilter('seller_id', ['eq' => $seller_id]);
            $seller_products = $productColl->getData();
            $chunk_data = $this->getArrayInChunks($seller_products);

            if (!empty($chunk_data)) {
                foreach ($chunk_data as $chunk) {
                    if ($seller_approval_type != \Hariyo\Marketplace\Helper\GridAction::APPROVED) {
                        $tmp_array = [];
                        foreach ($chunk as $productData) {
                            $tmp_array[] = $product_id = $productData['product_id'];
                            $action = $this->mp_statusActionModel->getCollection();
                            $action->addFieldToFilter('product_id', ['eq' => $product_id]);
                            $action->addFieldToFilter('seller_id', ['eq' => $seller_id]);
                            $action->addFieldToFilter('action', ['eq' => $action_type]);
                            if ($action->getSize() == 0) {
                                unset($action);
                                $insert_row = $this->mp_statusActionModel;
                                $insert_row->setAction($action_type);
                                $insert_row->setProductId($product_id);
                                $insert_row->setSellerId($seller_id);
                                $insert_row->save();
                                $insert_row->unsetData();
                            }
                            unset($action);
                        }
                        $this->updateProductStatus($tmp_array, \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED);
                    } else if ($seller_approval_type == \Hariyo\Marketplace\Helper\GridAction::APPROVED) {
                        $tmp_array = [];
                        foreach ($chunk as $productData) {
                            $product_id = $productData['product_id'];
                            $action = $this->mp_statusActionModel->getCollection();
                            $action->addFieldToFilter('product_id', ['eq' => $product_id]);
                            $action->addFieldToFilter('seller_id', ['eq' => $seller_id]);
                            $action->addFieldToFilter('action', ['eq' => $action_type]);
                            if ($action->getSize() == 1) {
                                $tmp_array[] = $product_id;
                            }
                            $action->walk('delete');
                            unset($action);
                        }
                        $this->updateProductStatus($tmp_array, \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
                    }
                }
            }
        } catch (\Exception $ex) {
            $this->mp_logHelper->createFileAndWriteLogData(
                    \Hariyo\Marketplace\Helper\Log::INFOTYPEERROR, 'Helper Product::statusUpdateOnSellerApproval()', $e->getMessage()
            );
        }
    }
    
    public function saveSellerOrderItem($sellerId, $orderId, $orderItems, $isNew = true) {
        try {
            $storeId = $this->mp_storeManager->getStore()->getStoreId();
            $websiteId = $this->mp_storeManager->getWebsite()->getId();
            if (!$isNew) {
                $tmp = $this->_orderModel->load($orderId);
                $tmp1 = $tmp->getData();
                $tmp->unsetData();
                $original_order_model = $this->_orderModel->load($tmp1['original_increment_id'], 'increment_id');
                $original_order = $original_order_model->getData();
                $original_order_model->undetData();
                $data = ['consider_for_calculations' => self::CALCULATION_NOT_ALLOWED];
                $model = $this->mp_orderItemModel->getCollection()->addFieldToFilter('order_id', $original_order['entity_id']);
                foreach ($model as $orderModel) {
                    $updateModel = $this->mp_orderItemModel->load($orderModel->getRowId())->addData($data);
                    $updateModel->setId($orderModel->getRowId())->save();
                    $updateModel->unsetData();
                }
                $earning_col = $this->mp_earningModel->load($original_order['entity_id'], 'order_id');
                $commision = $earning_col->getData('commision_percent');
                $earning_col->unsetData();
                unset($model);
            } else {
                $commision = (float) $this->mp_objectManager->get('\Hariyo\Marketplace\Helper\Setting')->getSettingByKey($sellerId, 'commission');
            }
            foreach ($orderItems as $item) {
                $product = $this->_productModel->load((int) $item['product_id']);
                $cats = $product->getCategoryIds();
                $product->unsetData();
                foreach ($cats as $category_id) {
                    $itemModel = $this->mp_orderItemModel;
                    $itemModel->setStoreId($storeId);
                    $itemModel->setWebsiteId($websiteId);
                    $itemModel->setSellerId($sellerId);
                    $itemModel->setOrderId($orderId);
                    $itemModel->setOrderItemId((int) $item['item_id']);
                    $itemModel->setCategoryId($category_id);
                    $itemModel->setConsiderForCalculations(self::CALCULATION_ALLOWED);
                    $itemModel->setCommission($commision);
                    $itemModel->setCreatedAt($this->date->date());
                    $itemModel->save();
                    $itemModel->unsetData();
                }
            }
        } catch (\Exception $ex) {
            $this->mp_logHelper->createFileAndWriteLogData(
                    \Hariyo\Marketplace\Helper\Log::INFOTYPEERROR, 'Helper Product::saveSellerOrderItem()', $ex->getMessage()
            );
        }
    }
    
    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function processBundleOptionsData(\Magento\Catalog\Model\Product $product)
    {
        $bundleOptionsData = $product->getBundleOptionsData();
        if (!$bundleOptionsData) {
            return;
        }
        $options = [];
        foreach ($bundleOptionsData as $key => $optionData) {
            if ((bool)$optionData['delete']) {
                continue;
            }

            $option = $this->mp_objectManager->create("\Magento\Bundle\Api\Data\OptionInterfaceFactory")->create(['data' => $optionData]);
            $option->setSku($product->getSku());
            $option->setOptionId(null);

            $links = [];
            $bundleLinks = $product->getBundleSelectionsData();
            if (empty($bundleLinks[$key])) {
                continue;
            }

            foreach ($bundleLinks[$key] as $linkData) {
                if ((bool)$linkData['delete']) {
                    continue;
                }
                $link = $this->mp_objectManager->create("\Magento\Bundle\Api\Data\LinkInterfaceFactory")->create(['data' => $linkData]);

                if ((int)$product->getPriceType() !== \Magento\Bundle\Model\Product\Price::PRICE_TYPE_DYNAMIC) {
                    if (array_key_exists('selection_price_value', $linkData)) {
                        $link->setPrice($linkData['selection_price_value']);
                    }
                    if (array_key_exists('selection_price_type', $linkData)) {
                        $link->setPriceType($linkData['selection_price_type']);
                    }
                }

                $linkProduct = $this->mp_objectManager->create('\Magento\Catalog\Api\ProductRepositoryInterface')->getById($linkData['product_id']);
                $link->setSku($linkProduct->getSku());
                $link->setQty($linkData['selection_qty']);

                if (array_key_exists('selection_can_change_qty', $linkData)) {
                    $link->setCanChangeQuantity($linkData['selection_can_change_qty']);
                }
                $links[] = $link;
            }
            $option->setProductLinks($links);
            $options[] = $option;
        }

        $extension = $product->getExtensionAttributes();
        $extension->setBundleProductOptions($options);
        $product->setExtensionAttributes($extension);
        return;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return void
     */
    public function processDynamicOptionsData(\Magento\Catalog\Model\Product $product)
    {
        if ((int)$product->getPriceType() !== \Magento\Bundle\Model\Product\Price::PRICE_TYPE_DYNAMIC) {
            return;
        }

        if ($product->getOptionsReadonly()) {
            return;
        }
        $product->setCanSaveCustomOptions(true);
        $customOptions = $product->getProductOptions();
        if (!$customOptions) {
            return;
        }
        foreach (array_keys($customOptions) as $key) {
            $customOptions[$key]['is_delete'] = 1;
        }
        $newOptions = $product->getOptions();
        foreach ($customOptions as $customOptionData) {
            if ((bool)$customOptionData['is_delete']) {
                continue;
            }
            $customOption = $this->mp_objectManager->create('\Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory')->create(['data' => $customOptionData]);
            $customOption->setProductSku($product->getSku());
            $customOption->setOptionId(null);
            $newOptions[] = $customOption;
        }
        $product->setOptions($newOptions);
    }

}
