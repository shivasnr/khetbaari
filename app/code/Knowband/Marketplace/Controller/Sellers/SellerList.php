<?php

namespace Knowband\Marketplace\Controller\Sellers;

use Knowband\Marketplace\Controller\Index\ParentController;
class SellerList extends ParentController {

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
            \Knowband\Marketplace\Helper\Setting $settingHelper,
            \Knowband\Marketplace\Helper\Seller $sellerHelper,
            \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context, $request, $response, $storeManager, $registry, $customerSessionModel, $resultRawFactory, $settingHelper, $sellerHelper);
        $this->mp_request = $request;
        $this->mp_storeManager = $storeManager;
        $this->mp_resultRawFactory = $resultRawFactory;
        $this->mp_settingHelper = $settingHelper;
        $this->_frameworkUrlInterface = $context->getUrl();
        $this->scopeConfig = $scopeConfig;
    }

    public function execute() {
        if ($this->scopeConfig->getValue('vss/marketplace/active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
            $resultPage = $this->mp_resultRawFactory->create();
            $resultPage->getConfig()->getTitle()->prepend(__("Seller List"));
            return $resultPage;
        }else{
            $this->getResponse()->setRedirect($this->_frameworkUrlInterface->getBaseUrl());
        }
    }

}
