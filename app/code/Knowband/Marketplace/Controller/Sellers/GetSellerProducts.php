<?php

namespace Knowband\Marketplace\Controller\Sellers;

use Knowband\Marketplace\Controller\Index\ParentController;
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
            \Knowband\Marketplace\Helper\Setting $settingHelper,
            \Knowband\Marketplace\Helper\Seller $sellerHelper,
            \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
            \Knowband\Marketplace\Helper\Log $logHelper,
            \Magento\Framework\View\LayoutFactory $viewLayoutFactory
    ) {
        parent::__construct($context, $request, $response, $storeManager, $registry, $customerSessionModel, $resultRawFactory, $settingHelper, $sellerHelper);
        $this->mp_request = $request;
        $this->mp_storeManager = $storeManager;
        $this->mp_resultRawFactory = $resultRawFactory;
        $this->mp_settingHelper = $settingHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->mp_logHelper = $logHelper;
        $this->_viewLayoutFactory = $viewLayoutFactory;
    }

    public function execute() {
        $result = $this->resultJsonFactory->create();
        
        try{
            return $result->setData($this->_viewLayoutFactory->create()->createBlock('Knowband\Marketplace\Block\Seller\Products')->toHtml());
        } catch (\Exception $ex) {
            $this->mp_logHelper->createFileAndWriteLogData(
                    \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Controller Sellers\GetSellerProducts::execute()', $ex->getMessage()
            );
            return $result->setData('<ul class="messages"><li class="error-msg"><ul><li><span>' . __('Error Occured') . '</span></li></ul></li></ul>');
        }
        
        
    }

}
