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
class Setting extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $mp_storeManager;
    protected $mp_scopeConfig;
    protected $mp_request;
    protected $rulesFactory;
    protected $mp_objectManager;
    
    CONST REGISTER_REQUEST_LIMIT = 3;
    private $_store_config = null;
    private $settingsToStore = ['commission', 'category_ids', 'product_approval', 'product_limit', 'seller_review', 'seller_review_approval_required', 'email_to_seller', 'earning_calculate'];

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\ResourceModel\Group\Collection $customerGroup,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configResource,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $category,
        \Magento\Backend\Model\Auth\Session $adminSession,
        \Hariyo\Marketplace\Model\Settings $mpSettingModel,
        \Hariyo\Marketplace\Model\Product $mpProductToSellerModel
    )
    {
        $this->mp_storeManager = $storeManager;
        $this->moduleManager = $context->getModuleManager();
        $this->mp_scopeConfig = $context->getScopeConfig();
        $this->mp_request = $context->getRequest();
        $this->mp_resource = $configResource;
        $this->mp_objectManager = $objectManager;
        $this->date = $date;
        $this->_adminSession = $adminSession;
        $this->mp_customerGroup = $customerGroup;
        $this->mp_settingModel = $mpSettingModel;
        $this->mp_productToSellerModel = $mpProductToSellerModel;
        $this->logger = $context->getLogger();
        $this->_categoryFactory = $category;
        parent::__construct($context);
    }

    public function getDate() {
        return $this->date->date();
    }
    
    private function setStoreConfig() {
        $this->_store_config = $this->mp_storeManager->getStore()->getData();
    }

    public function getMediaUrl() {
	$om = \Magento\Framework\App\ObjectManager::getInstance();
	$storeManager = $om->get('Magento\Store\Model\StoreManagerInterface');
	$currentStore = $storeManager->getStore();
	return $currentStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    public function getSettings($key = 'Hariyo/marketplace/general_settings', $front = false, $scope = "default", $scope_id = 0) {
        if ($this->mp_request->getParam('store')) {
            $scope_id = $this->mp_storeManager->getStore($this->mp_request->getParam('store'))->getId();
            $scope = "stores";
        } elseif ($this->mp_request->getParam('website')) {
            $scope_id = $this->mp_storeManager->getWebsite($this->mp_request->getParam('website'))->getId();
            $scope = "websites";
        } elseif ($this->mp_request->getParam('group')) {
            $scope_id = $this->mp_storeManager->getGroup($this->mp_request->getParam('group'))->getWebsite()->getId();
            $scope = "groups";
        } else {
            $scope = "default";
            $scope_id = 0;
        }
        if($front){
            $settings_json = $this->mp_scopeConfig->getValue($key, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }else{
            $settings_json = $this->mp_scopeConfig->getValue($key, $scope, $scope_id);
        }
        $settings_array = json_decode($settings_json, true);
        if (($settings_array === false || $settings_array === NULL) && $key == 'Hariyo/marketplace/general_settings') {
            $default_settings = $this->getDefaultGlobalSettings();
            if (isset($default_settings['enable_mp']) && $default_settings['enable_mp'] == '1') {
                $enable_mp = 1;
            } else {
                $enable_mp = 0;
            }
            if ($front) {
                $this->mp_resource->saveConfig("Hariyo/marketplace/general_settings", json_encode($default_settings), \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                $this->mp_resource->saveConfig('vss/marketplace/active', $enable_mp, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            } else {
                $this->mp_resource->saveConfig("Hariyo/marketplace/general_settings", json_encode($default_settings), $scope, $scope_id);
                $this->mp_resource->saveConfig('vss/marketplace/active', $enable_mp, $scope, $scope_id);
            }
            return $default_settings;
        } else {
            return $settings_array;
        }
    }
    
    public function getSettingBySeller($seller_id) {
        $collection = $this->mp_settingModel->getCollection()
                ->addFieldToFilter('seller_id', ['eq' => $seller_id]);
        $data = [];
        if ($collection->getSize()) {
            $settings = $collection->getData();
            unset($collection);
            foreach ($settings as $set) {
                $data[$set['field_name']] = $set['field_value'];
            }
        } else {
            $data = $this->getGlobalSetting();
        }
        return $data;
    }

    public function getSettingByKey($seller_id, $key) {
        $collection = $this->mp_settingModel->getCollection()
                ->addFieldToFilter('seller_id', ['eq' => $seller_id]);
        $collection->addFieldToFilter('field_name', ['eq' => $key]);
        
        if ($collection->getSize()) {
            $settings = $collection->getData();
            unset($collection);
            if ($settings[0]['use_default'] == 0) {
                return $settings[0]['field_value'];
            } else {
                return $this->getGlobalSettingByKey($key);
            }
        } else {
            return $this->getGlobalSettingByKey($key);
        }
    }
    

    public function getDefaultGlobalSettings() {
        $settings = [
            'enable_mp' => 1,
            'commission' => 15,
            'category_ids' => '',
            'register_as_seller' => 1,
            'product_limit' => 20,
            'seller_list' => 1
        ];
        return $settings;
    }
    
    public function getDefaultSellerSettings() {
        $settings = [
            'commission' => ['seller' => 15, 'global' => 1],
            'category_ids' => [
                'seller' => '',
                'global' => 1
            ],
            'product_limit' => ['seller' => 20, 'global' => 1],
            'email_to_seller' => ['seller' => 1, 'global' => 1]    
        ];
        return $settings;
    }

    
    /**
     * Function to get global setting
     * @return array
     */
    public function getGlobalSetting() {
       return $this->getSettings();
    }

    /**
     * Get Global Setting by key if found otherwise return false
     * @param string $key
     * @return string or false
     */
    public function getGlobalSettingByKey($key) {
        $settings = $this->getGlobalSetting();
        if (is_array($settings) && !empty($settings) && isset($settings[$key])) {
            return $settings[$key];
        } else {
            return false;
        }
    }
    
    /**
     * Save default seller settings data into the settings mapping table.
     * @param array $customer_data
     * @param int $store_id
     */
     public function saveDefaultSellerSettings($customer_data, $store_id) {
        try {
            $globalSettings = $this->getSettings();
            foreach($this->settingsToStore as $req_setting){
                if(!isset($globalSettings[$req_setting])){
                    $globalSettings[$req_setting] = '';
                }
            }
            foreach ($globalSettings as $index => $setting) {
                if (!in_array($index, $this->settingsToStore)){
                    
                    continue;
                }
                $settingModel = $this->mp_settingModel;
                $settingModel->setStoreId($store_id);
                $settingModel->setWebsiteId($customer_data['website_id']);
                $settingModel->setSellerId($customer_data['entity_id']);
                $settingModel->setFieldName($index);
                $settingModel->setFieldValue($setting);
                $settingModel->setUseDefault(1);
                $settingModel->setCreatedAt($this->getDate());
                $settingModel->setUpdatedAt($this->getDate());
                $settingModel->save();
                $settingModel->unsetData();
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            // $this->mp_logHelper->createFileAndWriteLogData(
            //         \Hariyo\Marketplace\Helper\Log::INFOTYPEERROR, 'Helper Setting::saveDefaultSellerSettings()', $e->getMessage()
            // );
        }
    }

    /**
     * Function to get total products of a seller
     * @param int $seller_id
     * @return int
     */
    public function countSellerProducts($seller_id) {
        $sellerProductsCollection = $this->mp_productToSellerModel->getCollection()->addFieldToFilter('seller_id', $seller_id);
        $count = $sellerProductsCollection->getSize();
        unset($sellerProductsCollection);
        return $count;
    }

   /**
    * Get all the system categories.
    * @return array
    */
    public function getSystemCategories()
    {
        $categories = [];
        try {
            $categoriesCol = $this->_categoryFactory->create()
                    ->addAttributeToSelect('name')
                    ->addAttributeToSort('path', 'asc')
                    ->addFieldToFilter('is_active', ['eq' => '1']);

            foreach ($categoriesCol as $category) {
                $categories[] = $category->getEntityId();
            }
            unset($categoriesCol);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            // $this->mp_logHelper->createFileAndWriteLogData(
            //         \Hariyo\Marketplace\Helper\Log::INFOTYPEERROR, 'Helper Setting::getSystemCategories()', $e->getMessage()
            // );
        }
        return $categories;
    }

    public function getDisableCategories($selected_categories = [])
    {
        $system_categries = $this->getSystemCategories();
        $disabled_cat = [];
        foreach($system_categries as $cat){
                if(!in_array($cat, $selected_categories)){
                    $disabled_cat[] = $cat;
                }
        }
        return $disabled_cat;
    }
    

    public function getStoreIdDetails() {
        if ($this->mp_request->getParam('store')) {
            $storeId = $this->mp_storeManager->getStore($this->mp_request->getParam('store'))->getId();
            $websiteId = 0;
            $scope = "stores";
        } elseif ($this->mp_request->getParam('website')) {
            $websiteId = $this->mp_storeManager->getWebsite($this->mp_request->getParam('website'))->getId();
            $storeId = 0;
            $scope = "websites";
        } elseif ($this->mp_request->getParam('group')) {
            $websiteId = $this->mp_storeManager->getGroup($this->mp_request->getParam('group'))->getWebsite()->getId();
            $storeId = 0;
            $scope = "groups";
        } else {
            $scope = "default";
            $websiteId = 0;
            $storeId = 0;
        }
        return $websiteId ? $websiteId : $storeId;
    }

    public function getBaseUrl($param) {
        if ($param == 'URL_TYPE_MEDIA') {
            return $this->mp_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        }
    }

    /*
     * Function to getting all the customer groups in the system.
     */

    public function getCustomerGroups() {
        $customerGroup = [];
        $customerGroups = $this->mp_customerGroup->toOptionHash();
        foreach (array_keys($customerGroups) as $key) {
            $customerGroup[] = $key;
        }
        return $customerGroup;
    }
    
    public function getCurrentCurrencyCode(){
        return $this->mp_storeManager->getStore()->getCurrentCurrency()->getCode();
    }
    
    public function getSalesName() {
        return $this->mp_scopeConfig->getValue(
                        'trans_email/ident_sales/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getSalesEmail() {
        return $this->mp_scopeConfig->getValue(
                        'trans_email/ident_sales/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getStoreName() {
        return $this->mp_scopeConfig->getValue(
                        'trans_email/ident_general/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getStoreEmail() {
        return $this->mp_scopeConfig->getValue(
                        'trans_email/ident_general/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    
    public function getStoreUrl(){
        return $this->mp_storeManager->getStore()->getBaseUrl();
    }

}
