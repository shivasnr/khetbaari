<?php

namespace Knowband\Marketplace\Block\Seller;

class SellerList extends \Magento\Framework\View\Element\Template {

    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Knowband\Marketplace\Model\Seller $mpSellerModel,
            \Knowband\Marketplace\Helper\Data $mpDataHelper
    ) {
        $this->_objectManager = $objectManager;
        $this->mp_sellerModel = $mpSellerModel;
        $this->_storeManager = $context->getStoreManager();
        $this->mp_dataHelper = $mpDataHelper;
        $this->scopeConfig = $context->getScopeConfig();
        parent::__construct($context);
        
        $collection = $this->mp_sellerModel->getCollection()
                ->addFieldToFilter('seller_approved', ['eq' => '1'])
                ->addFieldToFilter('seller_enabled', ['eq' => '1'])
                ->addFieldToFilter('store_id', ['in' => [$this->_storeManager->getStore()->getId(), '0']]);

        if ($this->getRequest()->getParam('seller_search_keyword')) {
            $collection->getSelect()->where("shop_title like '%" . $this->getRequest()->getParam('seller_search_keyword') . "%'");
        }
        $this->setCollection($collection);
    }
    
    protected function _prepareLayout() {
        parent::_prepareLayout();
//        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
//        $breadcrumbs->addCrumb('home', [
//            'label' => __('Home'),
//            'title' => __('Home Page'),
//            'link' => ''
////            'link' => Mage::getBaseUrl()
//        ]);
//        $breadcrumbs->addCrumb('seller_list', [
//            'label' => __('Sellers List'),
//            'title' => __('Sellers List')
//        ]);
//        $this->getLayout()->createBlock('catalog/breadcrumbs');
        $pager = $this->getLayout()->createBlock('Magento\Theme\Block\Html\Pager', 'seller.pager');
        $pager->setAvailableLimit(
                [
                    12 => 12,
                    24 => 24,
                    36 => 36,
                    'all' => __('All')
                ]
        );
        $pager->setCollection($this->getCollection());
        $this->setChild('pager', $pager);
        $this->getCollection()->load();
        return $this;
    }

    public function getPagerHtml() {
        return $this->getChildHtml('pager');
    }
    
    public function getSearchBoxSetting(){
        return $this->scopeConfig->getValue('vss/search_box/active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    public function getFrontUrl($controller, $action = '', $params = []){
        return $this->mp_dataHelper->getFrontUrl($controller, $action, $params);
    }

}
