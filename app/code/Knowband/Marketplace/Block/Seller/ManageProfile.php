<?php

namespace Knowband\Marketplace\Block\Seller;

class ManageProfile extends \Magento\Framework\View\Element\Template {
    
    private $seller_info = [];
    
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Framework\Registry $registry,
            \Magento\Directory\Model\CountryFactory $countryFactory,
            \Magento\Framework\Event\Manager $eventManager,
            \Knowband\Marketplace\Model\Seller $mpSellerModel
    ) {
        $this->_coreRegistry = $registry;
        $this->_eventManager = $eventManager;
        $this->_countryFactory = $countryFactory;
        $this->mp_sellerModel = $mpSellerModel;
        parent::__construct($context);
        $this->seller_info = $this->_coreRegistry->registry('vssmp_seller_info');
    }
    
    public function getSellerId() {
        return $this->seller_info['entity_id'];
    }

    public function getSellerData() {
        $sellerModel = $this->mp_sellerModel->load($this->getSellerId(), 'customer_id');
        $seller_data = $sellerModel->getData();
        $sellerModel->unsetData();
        $this->_eventManager->dispatch('seller_profile_display_before', ['seller_data' => &$seller_data]);
        return $seller_data;
    }

    public function getCountries() {
        $countries = [];
        $country_collection = $this->_countryFactory->create()->getResourceCollection()->loadByStore();
        $results = $country_collection->toOptionArray();
        foreach ($results as $ctr) {
            $countries[] = ['country_id' => $ctr['value'], 'name' => $ctr['label']];
        }
        unset($country_collection);
        return $countries;
    }
    
    public function getPaymentMethod(){
        return $this->mp_payoutHelper->getPaymentMethod();
    }

}
