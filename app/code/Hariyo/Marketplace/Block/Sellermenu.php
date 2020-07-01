<?php

namespace Hariyo\Marketplace\Block;

class Sellermenu extends \Magento\Framework\View\Element\Template {

    protected $_links = [];
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Framework\Registry $registry,
            \Magento\Framework\Event\Manager $eventManager,
            \Hariyo\Marketplace\Helper\Setting $mpSettingHelper, 
            \Psr\Log\LoggerInterface $mpLogHelper, 
            \Hariyo\Marketplace\Model\Seller $mpSellerModel
    ) {
        $this->mp_settingHelper = $mpSettingHelper;
        $this->mp_logHelper = $mpLogHelper;
        $this->mp_sellerModel = $mpSellerModel;
        $this->_coreRegistry = $registry;
        $this->_eventManager = $eventManager;
        parent::__construct($context);
        $this->setTemplate('seller_menu.phtml');
    }
    
    protected function _prepareLayout() {        
        
        parent::_prepareLayout();
        $general_settings = $this->mp_settingHelper->getSettings();
        $this->_links = [
            ['key' => 'marketplace/index/index', 'label' => __('Seller Dashboard'), 'url' => $this->getUrl('marketplace/index/index'), 'icon' => 'fa-tachometer'],
            ['key' => 'marketplace/profile/index', 'label' => __('Seller Profile'), 'url' => $this->getUrl('marketplace/profile/index'), 'icon' => 'fa-briefcase'],
            ['key' => 'marketplace/product/productlist', 'label' => __('Product Catalog'), 'url' => $this->getUrl('marketplace/product/productlist'), 'icon' => 'fa-cubes'],
            ['key' => 'marketplace/order/orderlist', 'label' => __('Orders'), 'url' => $this->getUrl('marketplace/order/orderlist'), 'icon' => 'fa-cart-arrow-down'],
            ['key' => 'marketplace/productreview/productlist', 'label' => __('Product Feedback'), 'url' => $this->getUrl('marketplace/productreview/productlist'), 'icon' => 'fa-thumbs-up'],
            ['key' => 'marketplace/earnings/index', 'label' => __('Earnings'), 'url' => $this->getUrl('marketplace/earnings/index'), 'icon' => 'fa-credit-card']
        ];
        
        $this->_eventManager->dispatch(
                'render_marketplace_seller_menus', ['menus' => &$this->_links]
        );
    }

    public function getLinks() {
        return $this->_links;
    }

    public function isActive($key) {
        $request = $this->getRequest();
        $controllerName = $request->getControllerName();
        $moduleName = $request->getModuleName();
        $actionName = $request->getActionName();
        $activeLink = $moduleName . '/' . $controllerName . '/' . $actionName;
        if (trim($key) == trim($activeLink)) {
            return true;
        }
        return false;
    }

}
