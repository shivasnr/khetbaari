<?php

namespace Knowband\Marketplace\Controller\Index;

use Knowband\Marketplace\Controller\Index\ParentController;
class Index extends ParentController {

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
            \Knowband\Marketplace\Helper\Log $logHelper
    ) {
        parent::__construct($context, $request, $response, $storeManager, $registry, $customerSessionModel, $resultRawFactory, $settingHelper, $sellerHelper);
        $this->mp_request = $request;
        $this->mp_resultRawFactory = $resultRawFactory;
        $this->mp_settingHelper = $settingHelper;
        $this->mp_logHelper = $logHelper;
        
        $this->storemanager = $storeManager;
    }

    public function execute() {
        try {
            $this->isLoggedIn();
            
            $resultPage = $this->mp_resultRawFactory->create();
            // echo 'ok';
            // exit;
            $resultPage->getConfig()->getTitle()->prepend(__('Seller Dashboard'));
            
            $this->mp_resultRawFactory->create()->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
            $module_settings = $this->mp_settingHelper->getSettings();
            if (isset($module_settings['enable_mp']) && $module_settings['enable_mp'] == 1) {
                
            } else {
                return $this->resultRedirectFactory->create()->setUrl($this->_redirect->getRedirectUrl());
            }
            return $resultPage;
        } catch (\Exception $ex) {
            
            $this->mp_logHelper->createFileAndWriteLogData(
                    \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Controller Index::execute()', $ex->getMessage()
            );
            $this->messageManager->addError($ex->getMessage());
        }
    }

}
