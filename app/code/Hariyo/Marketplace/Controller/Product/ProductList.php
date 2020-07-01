<?php

namespace Hariyo\Marketplace\Controller\Product;

use Hariyo\Marketplace\Controller\Index\ParentController;
class ProductList extends ParentController {

    protected $mp_resultRawFactory;
    protected $mp_request;
    protected $mp_scopeConfig;
    protected $inlineTranslation;
    protected $mp_transportBuilder;

    public function __construct(
            \Magento\Framework\App\Action\Context $context, 
            \Magento\Framework\App\Request\Http $request, 
            \Magento\Framework\App\Response\Http $response,
            \Magento\Store\Model\StoreManagerInterface $storeManager,
            \Magento\Framework\Registry $registry,
            \Magento\Customer\Model\Session $customerSessionModel,
            \Magento\Framework\View\Result\PageFactory $resultRawFactory,
            \Hariyo\Marketplace\Helper\Setting $settingHelper,
            \Hariyo\Marketplace\Helper\Seller $sellerHelper
    ) {
        parent::__construct($context, $request, $response, $storeManager, $registry, $customerSessionModel, $resultRawFactory, $settingHelper, $sellerHelper);
        $this->mp_request = $request;
        $this->mp_storeManager = $storeManager;
        $this->mp_resultRawFactory = $resultRawFactory;
        $this->mp_settingHelper = $settingHelper;
    }

    public function execute() {
        
        $this->isLoggedIn();
        $resultPage = $this->mp_resultRawFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__("Seller's Product"));
        return $resultPage;
    }
    
//    public function preDispatch()
//    {
//        $this->isLoggedIn();
//        if (Mage::registry('vssmp_current_product')) {
//            Mage::unregister('vssmp_current_product');
//        }
//
//        parent::preDispatch();
//        return $this;
//    }
}
