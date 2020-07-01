<?php

namespace Hariyo\Marketplace\Controller\Index;

class ParentController extends \Magento\Framework\App\Action\Action {

    protected $mp_resultRawFactory;
    protected $mp_request;
    protected $mp_scopeConfig;
    protected $inlineTranslation;
    protected $mp_transportBuilder;
    
    protected $_isLogged = false;
    protected $_customerInfo = ['entity_id' => 0];
    protected $_current_store = 0;
    protected $_isSeller = false;
    
    protected $_edit_mode = false;
    protected $_posted_form_data = [];
    protected $_product_id = null;
    protected $_defaultStoreId = 0;
    protected $_logger;

    public function __construct(
            \Magento\Framework\App\Action\Context $context, 
            \Magento\Framework\App\Request\Http $request, 
            \Magento\Framework\App\Response\Http $response,
            \Magento\Store\Model\StoreManagerInterface $storeManager,
            \Magento\Framework\Registry $registry,
            \Magento\Customer\Model\Session $customerSessionModel,
            \Magento\Framework\View\Result\PageFactory $resultRawFactory,
            \Hariyo\Marketplace\Helper\Setting $settingHelper,
            \Hariyo\Marketplace\Helper\Seller $sellerHelper,
            \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->mp_request = $request;
        $this->mp_response = $response;
        $this->_storeManager = $storeManager;
        $this->_objectManager = $context->getObjectManager();
        $this->_customerSessionModel = $customerSessionModel;
        $this->_registry = $registry;
        $this->mp_resultRawFactory = $resultRawFactory;
        $this->mp_settingHelper = $settingHelper;
        $this->mp_sellerHelper = $sellerHelper;
        $this->_logger = $logger;
        
        $this->_urlInterface = $this->_objectManager->get('Magento\Framework\UrlInterface');
        
        $this->_shipmentModel = $this->_objectManager->get('\Magento\Sales\Model\Order\Shipment');
       
        $this->_orderModel = $this->_objectManager->get('\Magento\Sales\Model\Order');
        
        $this->checkPluginStatus();
        $this->_current_store = $this->_storeManager->getStore()->getId();

        $session = $this->_customerSessionModel;
        if ($session->isLoggedIn()) {
            $this->_customerInfo = $session->getCustomer()->getData();
            if ($this->_registry->registry('vssmp_seller_info')) {
                $this->_registry->unregister('vssmp_seller_info');
            }
            $this->_registry->register('vssmp_seller_info', $this->_customerInfo);
            
            $this->_isSeller = $this->mp_sellerHelper->isSeller();
            if (!$this->_isSeller) {
                $this->_isLogged = false;
            } else {
                $this->_isLogged = true;
            }
        } else {
            $this->_registry->unregister('vssmp_seller_info');
        }
    }

    public function execute() {
    }
    

    protected function isLoggedIn() {
        try {
            $login_url = $this->_urlInterface->getUrl('customer/account');
            $moduleName = $this->getRequest()->getModuleName();
            $controllerName = $this->getRequest()->getControllerName();
            $actionName = $this->getRequest()->getActionName();
            if ($moduleName == 'marketplace' && $controllerName == 'sellers') {
                if ($actionName == 'list' || $actionName == 'view') {
                    return true;
                } else if ($actionName == 'reviewcustomerview') {
                    $isLoggedIn = $this->_customerSessionModel->isLoggedIn();
                    if (!$isLoggedIn) {
                        $this->mp_response->setRedirect($login_url)->sendResponse();
                    }
                } else {
                    if (!$this->_isSeller) {
                        $this->mp_response->setRedirect($login_url)->sendResponse();
                    }

                    if (!$this->_isLogged) {
                        $this->mp_response->setRedirect($login_url)->sendResponse();
                    }
                }
            } else {
                if (!$this->_isSeller) {
                    $this->mp_response->setRedirect($login_url)->sendResponse();
                }

                if (!$this->_isLogged) {
                    $this->mp_response->setRedirect($login_url)->sendResponse();
                }
            }
        } catch (\Exception $ex) {
            $this->messageManager->addError($ex->getMessage());
            $this->redirectToDashboard();
        }
    }

    protected function redirectToDashboard()
    {
        $this->_redirect('*/index');
    }

    protected function validateParamId($model, $columns) {
        try {
            $this->isLoggedIn();
            $loadedModel = $this->_objectManager->create($model);
            $collection = $loadedModel->getCollection();
            $collection->addFieldToFilter('seller_id', ['eq' => $this->_customerInfo['entity_id']]);
            foreach ($columns as $name => $value) {
                $collection->addFieldToFilter($name, ['eq' => $value]);
            }

            $col_size = $collection->getSize();
            $loadedModel->unsetData();
            unset($collection);
            if ($col_size <= 0) {
                $this->redirectToDashboard();
            }
        } catch (\Exception $ex) {
            $this->messageManager->addError($ex->getMessage());
            $this->redirectToDashboard();
        }
    }

    protected function checkPluginStatus()
    {
        $settings = $this->mp_settingHelper->getSettings();
        if (isset($settings['enable_mp']) && $settings['enable_mp']) {
        }else{
            $this->_redirect($this->_storeManager->getStore()->getBaseUrl());
        }
    }
    
    public function isValidOrder()
    {
        if (!$this->getRequest()->getParam('order_id')) {
            $this->redirectToDashboard();
        }
        $this->validateParamId('\Hariyo\Marketplace\Model\Earnings', ['order_id' => $this->getRequest()->getParam('order_id')]);
    }
    
    protected function _initProduct() {
        $this->_registry->unregister('vssmp_current_product');
        $productId = (int) $this->getRequest()->getParam('id');
        if ($this->getRequest()->getParam('edit_mode')) {
            $this->_edit_mode = true;
        }
        $this->_posted_form_data['id'] = $productId;
        $product = $this->_objectManager->create('Magento\Catalog\Model\Product')
                ->setStoreId($this->getRequest()->getParam('store', 0));

        if (!$productId) {
            if ($setId = (int) $this->getRequest()->getParam('set')) {
                $this->_posted_form_data['set'] = $setId;
                $product->setAttributeSetId($setId);
            }

            if ($typeId = $this->getRequest()->getParam('type')) {
                $this->_posted_form_data['type'] = $typeId;
                $product->setTypeId($typeId);
            }
        } else {
            try {
                $product->load($productId);
            } catch (\Exception $ex) {
                $product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);
                $this->messageManager->addError($ex->getMessage());
            }
            $this->_posted_form_data['set'] = $product->getAttributeSetId();
            $this->_posted_form_data['type'] = $product->getTypeId();
        }

        $attributes = $this->getRequest()->getParam('attributes');
        $this->_posted_form_data['attributes'] = $attributes;
        if ($attributes && count($attributes) > 0 && $product->isConfigurable() &&
                (!$productId || !$product->getTypeInstance()->getUsedProductAttributeIds())) {
            $product->getTypeInstance()->setUsedProductAttributeIds($attributes);
        }

        // Required attributes of simple product for configurable creation
        if ($this->getRequest()->getParam('popup') && $requiredAttributes = $this->getRequest()->getParam('required')) {
            $this->_posted_form_data['required'] = $requiredAttributes;
            $requiredAttributes = explode(",", $requiredAttributes);
            foreach ($product->getAttributes() as $attribute) {
                if (in_array($attribute->getId(), $requiredAttributes)) {
                    $attribute->setIsRequired(1);
                }
            }
        }

        $allowed_categories = [];
        $setting_categories = $this->mp_settingHelper->getSettingByKey($this->_customerInfo['entity_id'], 'category_ids');
        if (isset($setting_categories['seller']) && trim($setting_categories['seller'], ',') != '') {
            $allowed_categories = explode(',', $setting_categories['seller']);
        } else if (!is_array($setting_categories) && $setting_categories != '') {
            $allowed_categories = explode(',', $setting_categories);
        }
        $this->_registry->unregister('allowed_categories');
        $this->_registry->register('allowed_categories', array_unique($allowed_categories));
        
        $this->_registry->register('vssmp_current_product', $product);
        return $product;
    }
}
