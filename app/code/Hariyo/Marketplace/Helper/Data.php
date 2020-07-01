<?php

/**
 * Hariyo_Marketplace
 *
 * @category    Hariyo
 * @package     Hariyo_Marketplace
 * @author      Hariyo Team <support@Hariyo.com.com>
 * @copyright   Hariyo (http://wwww.Hariyo.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Hariyo\Marketplace\Helper;
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $mp_storeManager;
    protected $mp_scopeConfig;
    protected $mp_request;
    protected $rulesFactory;
    protected $mp_objectManager;
    
    CONST PAGELIMIT = 20;
    CONST MAX_RATING = 5;
    CONST TEXT_CHARACTER_LIMIT = 200;
    CONST PRODUCT_NAME_TEXT_LIMIT = 35;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\State $state,
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configResource,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Catalog\Model\Category $categoryModel,
        \Magento\Customer\Model\Session $customerSession,
        \Hariyo\Marketplace\Model\Settings $mpSettingModel,
        \Hariyo\Marketplace\Model\Seller $mpSellerModel,
        \Hariyo\Marketplace\Helper\Setting $mpSettingHelper,
        \Hariyo\Marketplace\Helper\Seller $mpSellerHelper,
        \Hariyo\Marketplace\Helper\Product $mpProductHelper,
        \Psr\Log\LoggerInterface $mpLogger
    )
    {
        $this->mp_storeManager = $storeManager;
        $this->moduleManager = $context->getModuleManager();
        $this->mp_scopeConfig = $context->getScopeConfig();
        $this->mp_request = $context->getRequest();
        $this->mp_resource = $configResource;
        $this->_priceHelper = $priceHelper;
        $this->mp_objectManager = $objectManager;
        $this->date = $date;
        $this->state = $state;
        $this->_registry = $registry;
        $this->_customerSession = $customerSession;
        $this->_eventManager = $eventManager;
        $this->mp_settingModel = $mpSettingModel;
        $this->mp_sellerModel = $mpSellerModel;
        $this->mp_logHelper = $mpLogger;
        $this->mp_settingHelper = $mpSettingHelper;
        $this->mp_sellerHelper = $mpSellerHelper;
        $this->mp_productHelper = $mpProductHelper;
        $this->_categoryModel = $categoryModel;
        parent::__construct($context);
    }

    public function getDate() {
        return $this->date->date();
    }  
    
    public function getPageLength() {
        return self::PAGELIMIT;
    }
    
    public function getProduct() {
        return $this->_registry->registry('vssmp_current_product');
    }

    public function getSellerInfo() {
        return $this->_registry->registry('vssmp_seller_info');
    }
    
    public function getFrontUrl($controller, $action = null, $params = []) {
        $url = 'retail/' . $controller;
        if (!empty($action)) {
            $url .= '/' . $action;
        }
        return $this->_getUrl($url, $params);
    }

    
    public function saveInitialSellerData($customer_data, $createdFromBackEnd = false) {
        try {
            $area = $this->state->getAreaCode();
            if ($area == 'frontend') {
                $createdFromBackEnd = false;
            } else {
                $createdFromBackEnd = true;
            }
            $check_exist = $this->mp_sellerModel->load($customer_data['entity_id'], 'seller_id');
            if (!empty($check_exist->getData())) {
                if ($check_exist->getRegisterLimit() == 0) {
                    return false;
                } else {
                    $check_exist->setRegisterLimit((int) $check_exist->getRegisterLimit() - 1);
                    $check_exist->setSellerApproved(1);  //Set the seller approved.
                    $check_exist->setUpdatedAt($this->getDate());
                    $check_exist->save();
                    $check_exist->unsetData();
                }
            } else {
                if ($createdFromBackEnd) {
                    $store_id = $this->mp_storeManager->getWebsite((int) $customer_data['website_id'])
                            ->getDefaultGroup()
                            ->getDefaultStoreId();
                } else {
                    $store_id = $this->mp_storeManager->getStore()->getId();
                }

                $model = $this->mp_sellerModel;
                $model->setSellerId((int) $customer_data['entity_id']);
                $model->setCustomerId((int) $customer_data['entity_id']);
                $model->setStoreId($store_id);
                $model->setSellerApproved(1); //for free version
                $model->setSellerEnabled(1); // for free version
                $model->setWebsiteId((int) $customer_data['website_id']);
                $model->setRegisterLimit(\Hariyo\Marketplace\Helper\Setting::REGISTER_REQUEST_LIMIT);
                $model->setCreatedAt($this->getDate());
                $model->setUpdatedAt($this->getDate());
                $model->save();
                $model->unsetData();
                $this->mp_settingHelper->saveDefaultSellerSettings($customer_data, $store_id);
               // $this->mp_emailHelper->sendWelcomeSellerEmail($customer_data);
            }

            //$this->mp_emailHelper->sendSellerRegistrationNotificationEmail($customer_data);
            unset($customer_data);
        } catch (\Exception $e) {
            // $this->mp_logHelper->createFileAndWriteLogData(
            //         \Hariyo\Marketplace\Helper\Log::INFOTYPEERROR, 'Helper Data::saveInitialSellerData()', $e->getMessage()
            // );
            return false;
        }
        return true;
    }

    public function getCategoriesArray($allowedCategories = []) {
        $cat_array = [];
        $categoriesArray = $this->_categoryModel
                ->getCollection()
                ->addAttributeToSelect('name')
                ->addAttributeToSort('path', 'asc')
                ->addFieldToFilter('is_active', ['eq' => 1]);

        foreach ($categoriesArray as $category) {
            if (in_array((int) $category['entity_id'], $allowedCategories)) {
                continue;
            }
            $cat_array[] = [
                'name' => $category->getName(),
                'level' => $category->getLevel(),
                'id' =>  $category->getEntityId()
            ];
        }
        unset($categoriesArray);
        return $cat_array;
    }

    public function getCategoryDropDownHtml($categories, $cat_seller_req = false) {
            $selected_category = $this->mp_request->getParam('category_to_filter');
            $html = "";
            foreach($categories as $value){                                
                foreach($value as $key => $val){
                    if($key=='name'){
                        $catNameIs = $val;
                    }
                    if($key=='id'){
                        $catIdIs = $val;
                    }
                    if($key=='level'){
                        $catLevelIs = $val;
                        $b ='';
                        for($i=1;$i<$catLevelIs;$i++){
                            $b = $b. \Hariyo\Marketplace\Block\Product\Section\Category::CHILD_REL_SYMBOL;
                        }
                    }
                }
                
                if($cat_seller_req){
                    if($selected_category == $catIdIs){
                        $html .= "<option selected='selected' ".$disabled." value=".$catIdIs.">".$b.$catNameIs."</option>";
                    }else{
                        $html .= "<option ".$disabled." value=".$catIdIs.">".$b.$catNameIs."</option>";
                    }
                }else{
                    //make persist category
                    if($selected_category == $catIdIs){
                        $html .= "<option selected='selected'  value=".$catIdIs.">".$b.$catNameIs."</option>";
                    }else{
                        $html .= "<option  value=".$catIdIs.">".$b.$catNameIs."</option>";
                    }
                }
            }
            return $html;
        }
    
    /**
     * 
     * @param type $profile_data
     * @param type $seller_id
     */
    public function saveSellerProfileData($profile_data, $seller_id) {
        try {
            $shop_address = [
                'line1' => $profile_data['mp_frontProfile']['address']['line1'],
                'line2' => $profile_data['mp_frontProfile']['address']['line2'],
                'city' => $profile_data['mp_frontProfile']['address']['city'],
                'pincode' => $profile_data['mp_frontProfile']['address']['pincode'],
                'state' => $profile_data['mp_frontProfile']['address']['state'],
                'country' => $profile_data['mp_frontProfile']['address']['country']
            ];
            
            $data = [
                'shop_title' => $profile_data['mp_frontProfile']['shop_title'],
                'contact_number' => $profile_data['mp_frontProfile']['contact_number'],
                'shop_address' => json_encode($shop_address),
                'shop_country' => $profile_data['mp_frontProfile']['address']['country'],
                'description' => $profile_data['mp_frontProfile']['shop_desc'],
                'meta_keywords' => $profile_data['mp_frontProfile']['meta_keywords'],
                'meta_description' => $profile_data['mp_frontProfile']['meta_desc'],
                'return_policy' => $profile_data['mp_frontProfile']['return_policy'],
                'shipping_policy' => $profile_data['mp_frontProfile']['shipping_policy'],
                'payment_info' => '',
                'fb_link' => $profile_data['mp_frontProfile']['fb_link'],
                'google_link' => $profile_data['mp_frontProfile']['google_link'],
                'twitter_link' => $profile_data['mp_frontProfile']['twitter_link']
            ];

            if (isset($profile_data['mp_frontProfile']['page_url_key']) && !empty($profile_data['mp_frontProfile']['page_url_key'])) {
                $data['page_url_key'] = $seller_id . '-' . $profile_data['mp_frontProfile']['page_url_key'];
            } else if (!empty($profile_data['mp_frontProfile']['shop_title'])) {
                $data['page_url_key'] = $this->mp_sellerHelper->generate_seller_page_url($profile_data['mp_frontProfile']['shop_title'], $seller_id);
            } else {
                $data['page_url_key'] = '';
            }
            if (isset($profile_data['logo']) && !empty($profile_data['logo'])){
                $data['shop_logo'] = $profile_data['logo'];
            }

            if (isset($profile_data['banner']) && !empty($profile_data['banner'])){
                $data['shop_banner'] = $profile_data['banner'];
            }

            $model = $this->mp_sellerModel->load($seller_id, 'seller_id');
            $entity_id = $model->getSellerEntityId();
            $model->addData($data);
            $model->setId($entity_id)->save();
            $model->unsetData();

            $this->_eventManager->dispatch("seller_profile_save_after", ['request_data' => $data, 'seller_id' => $entity_id]);
        } catch (\Exception $ex) {
            // $this->mp_logHelper->createFileAndWriteLogData(
            //                 \Hariyo\Marketplace\Helper\Log::INFOTYPEERROR, 'Helper Data::saveSellerProfileData()', $ex->getMessage()
            // );
        }
    }

    /**
    * 
    * @param type $setting_data
    */
   public function saveSellerSettings($setting_data, $sellerId = 0) {
        try {
            $sellerCollection = $this->mp_sellerModel->load($sellerId, 'seller_id');
            $storeId = $sellerCollection->getStoreId();
            $websiteId = $sellerCollection->getWebsiteId();
            $sellerCollection->unsetData();        
            $collection = $this->mp_settingModel->getCollection()
                    ->addFieldToFilter('store_id', $storeId)
                    ->addFieldToFilter('seller_id', $sellerId);
            $collection->walk('delete');
            unset($collection);
            $selected_category = [];
            if (isset($setting_data['category_ids']['seller']) && isset($setting_data['category_ids']['global']) && $setting_data['category_ids']['global'] == 0) {
                if (substr($setting_data['category_ids']['seller'], 0, 1) === ',') {
                    $setting_data['category_ids']['seller'] = array_unique(explode(",", substr($setting_data['category_ids']['seller'], 1)));
                } else {
                    $setting_data['category_ids']['seller'] = array_unique(explode(",", $setting_data['category_ids']['seller']));
                }
                $setting_data['category_ids']['seller'] = implode(',', $setting_data['category_ids']['seller']);
                if ($setting_data['category_ids']['seller'] != '') {
                    $selected_category = explode(',', $setting_data['category_ids']['seller']);
                }
            }
            $category_setting_is = 'seller';

            foreach ($setting_data as $index => $setting) {
                $settingModel = $this->mp_settingModel;
                $settingModel->setStoreId($storeId);
                $settingModel->setWebsiteId($websiteId);
                $settingModel->setSellerId($sellerId);
                $settingModel->setFieldName($index);

                if ($index == 'category_ids' && isset($setting['global']) && $setting['global'] == 1) {
                    $global_category = $this->mp_settingHelper->getGlobalSettingByKey('category_ids');
                    if ($global_category && isset($global_category['seller']) && $global_category['seller'] != '') {
                        $selected_category = array_unique(explode(',', $global_category['seller']));
                        $category_setting_is = 'global';
                    }
                }
                if (isset($setting['global']) && $setting['global'] == 1){
                    $settingModel->setUseDefault(1);
                }else{
                    $settingModel->setUseDefault(0);
                    if (isset($setting['seller'])) {
                        if($index == 'category_ids'){
                            $settingModel->setFieldValue(implode(",", $selected_category));
                        }else{
                            $settingModel->setFieldValue($setting['seller']);
                        }
                    }
                }

                $settingModel->setCreatedAt($this->getDate());
                $settingModel->setUpdatedAt($this->getDate());
                $settingModel->save();
                $settingModel->unsetData();
            }

            
            if (empty($selected_category)) {
                $this->mp_productHelper->updateCatgoryMapping([], \Hariyo\Marketplace\Helper\Product::CAT_MAP, $category_setting_is, $sellerId);
            } else if (!empty($selected_category)) {
                $disable_cats = $this->mp_settingHelper->getDisableCategories($selected_category);
                $this->mp_productHelper->updateCatgoryMapping($disable_cats, \Hariyo\Marketplace\Helper\Product::CAT_UNMAP, $category_setting_is, $sellerId);
                $this->mp_productHelper->updateCatgoryMapping($selected_category, \Hariyo\Marketplace\Helper\Product::CAT_MAP, $category_setting_is, $sellerId);
            }
        } catch (\Exception $ex) {
            // $this->mp_logHelper->createFileAndWriteLogData(
            //                 \Hariyo\Marketplace\Helper\Log::INFOTYPEERROR, 'Helper Data::saveSellerSettings()', $ex->getMessage()
            //         );
        }
    }
    
    public function getColOrder($request_data) {
        $col_index = $request_data['order'][0]['column'];
        $col_name = $request_data['columns'][$col_index]['name'];
        $col_dir = $request_data['order'][0]['dir'];
        return ['col' => $col_name, 'dir' => $col_dir];
    }
    
    public function formatCurrency($curreny) {
        return $this->_priceHelper->currency($curreny, true, false);
    }
    
    public function getRatingOptionArray() {
        $data = [];
        for ($i = 1; $i <= self::MAX_RATING; $i++) {
            $data[] = ['label' => $i, 'value' => $i];
        }
        return $data;
    }

    public function clipLongText($text = '', $read_more_link = '', $length = self::TEXT_CHARACTER_LIMIT) {
        if (strlen($text) > $length) {
            $text = $this->mp_objectManager->get("\Magento\Framework\Escaper")->escapeHtml($text);
            $text = substr($text, 0, $length) . '...';
            if ($read_more_link != '') {
                $text = $text . $read_more_link;
            }
        }
        return $text;
    }
    
    public function getSellerLevelSettingsBySellerId($seller_id) {
        return $this->mp_settingModel->getCollection()
                        ->addFieldToFilter('seller_id', $seller_id);
    }
    
    public function getProductReviewSummary($productId, $storeId) {
       return $this->mp_objectManager->create("\Magento\Review\Model\Review\Summary")->setStoreId($storeId)->load($productId);
        }
    
    public function loadProductByProductId($product_id = 0){
        return $this->mp_objectManager->create("\Magento\Catalog\Model\Product")->load((int)$product_id);
    }
    
    /**
     * Get Sellers count
     * @return int
     */
    public function getSellersCount() {
        try {
            return $this->mp_sellerModel->getCollection()->getSize();
        } catch (\Exception $ex) {
            return 0;
        }
    }

}
