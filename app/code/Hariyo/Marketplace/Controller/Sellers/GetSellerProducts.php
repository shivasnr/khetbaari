<?php

namespace Hariyo\Marketplace\Controller\Sellers;

use Hariyo\Marketplace\Controller\Index\ParentController;
class GetSellerProducts extends ParentController {

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
            \Hariyo\Marketplace\Helper\Seller $sellerHelper,
            \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
            \Magento\Framework\View\LayoutFactory $viewLayoutFactory
    ) {
        parent::__construct($context, $request, $response, $storeManager, $registry, $customerSessionModel, $resultRawFactory, $settingHelper, $sellerHelper);
        $this->mp_request = $request;
        $this->mp_storeManager = $storeManager;
        $this->mp_resultRawFactory = $resultRawFactory;
        $this->mp_settingHelper = $settingHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_viewLayoutFactory = $viewLayoutFactory;
    }

    public function execute() {
        $result = $this->resultJsonFactory->create();
        
        try{
            return $result->setData($this->_viewLayoutFactory->create()->createBlock('Hariyo\Marketplace\Block\Seller\Products')->toHtml());
        } catch (\Exception $ex) {
            $this->_logger->info($ex->getMessage());
            return $result->setData('<ul class="messages"><li class="error-msg"><ul><li><span>' . __('Error Occured') . '</span></li></ul></li></ul>');
        }
        
        
    }

}
