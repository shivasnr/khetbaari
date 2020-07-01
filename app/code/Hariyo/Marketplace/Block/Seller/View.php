<?php

namespace Hariyo\Marketplace\Block\Seller;

class View extends \Magento\Framework\View\Element\Template {

    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Hariyo\Marketplace\Model\Seller $mpSellerModel,
            \Hariyo\Marketplace\Helper\Data $mpDataHelper
    ) {
        $this->_objectManager = $objectManager;
        $this->mp_sellerModel = $mpSellerModel;
        $this->_storeManager = $context->getStoreManager();
        $this->mp_dataHelper = $mpDataHelper;
        $this->scopeConfig = $context->getScopeConfig();
        parent::__construct($context);
    }
    
    protected function _prepareLayout() {
        parent::_prepareLayout();
//        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
//        $breadcrumbs->addCrumb('home', array(
//            'label' => Mage::helper('marketplace')->__('Home'),
//            'title' => Mage::helper('marketplace')->__('Home Page'),
//            'link' => Mage::getBaseUrl()
//        ));
//        $breadcrumbs->addCrumb('seller_list', array(
//            'label' => Mage::helper('marketplace')->__('Sellers List'),
//            'title' => Mage::helper('marketplace')->__('Sellers List'),
//            'link' => Mage::getUrl('marketplace/sellers/list')
//        ));
//        $seller_id = $this->getRequest()->getParam('id');
//        if ($seller_id) {
//            $sellerModel = Mage::getModel('marketplace/marketplace')->load($seller_id, 'seller_id');
//            $sellerTitle = $sellerModel->getShopTitle();
//            if (empty($sellerTitle))
//                $sellerTitle = Mage::helper('marketplace')->__('Not Available');
//
//            $breadcrumbs->addCrumb('seller_name', array(
//                'label' => $sellerTitle,
//                'title' => $sellerTitle
//            ));
//        }
//        $this->getLayout()->createBlock('catalog/breadcrumbs');
//
//        if ($seller_id) {
//            $head = $this->getLayout()->getBlock('head');
//            if ($head) {
//                $head->setTitle($sellerModel->getShopTitle());
//                $head->setDescription($sellerModel->getMetaDescription());
//                $head->setKeywords($sellerModel->getMetaKeywords());
//            }
//            unset($sellerModel);
//        }
        return $this;
    }

//    public function getPagerHtml() {
//        return $this->getChildHtml('pager');
//    }
//    
//    public function getFrontUrl($controller, $action = '', $params = []){
//        return $this->mp_dataHelper->getFrontUrl($controller, $action, $params);
//    }

}
