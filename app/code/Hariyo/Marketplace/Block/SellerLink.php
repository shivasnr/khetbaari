<?php

namespace Hariyo\Marketplace\Block;

class SellerLink extends \Magento\Framework\View\Element\Template {

    public $product_id = 0;
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Framework\Registry $registry,
            \Hariyo\Marketplace\Helper\Setting $mpSettingHelper, 
            \Hariyo\Marketplace\Helper\Data $mpDataHelper, 
            \Hariyo\Marketplace\Model\Product $mpProductSellerModel
    ) {
        $this->mp_settingsHelper = $mpSettingHelper;
        $this->mp_dataHelper = $mpDataHelper;
        $this->mp_productToSellerModel = $mpProductSellerModel;
        $this->_coreRegistry = $registry;
        $this->_storeManager = $context->getStoreManager();
        parent::__construct($context);
        $this->product_id =  $this->getRequest()->getParam('id');
    }

    public function getSellerInfo() {
        $storeId = $this->_storeManager->getStore()->getId();

        $seller_info = $this->mp_productToSellerModel->getCollection();
//		$seller_info->addFieldToFilter('main_table.store_id', array('eq' => $store_id));
        $seller_info->addFieldToFilter('main_table.product_id', ['eq' => $this->product_id]);

        $seller_info->getSelect()->join(['e1' => $seller_info->getTable('vss_mp_seller_entity')], 'e1.seller_id=main_table.seller_id');

        $seller_data = $seller_info->getData();
        
        unset($seller_info);

        if (!empty($seller_data)) {
            $sellerData = $seller_data[0];
            
            return $sellerData;
        } else {
            return false;
        }
    }
    
    public function getSettingByKey($seller_id, $key){
        return $this->mp_settingsHelper->getSettingByKey($seller_id, $key);
    }

}
