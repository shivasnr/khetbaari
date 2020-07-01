<?php

namespace Knowband\Marketplace\Controller\Product;

use Knowband\Marketplace\Controller\Index\ParentController;
class GetAttributesSet extends ParentController {

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
            \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
            \Knowband\Marketplace\Helper\Setting $settingHelper,
            \Knowband\Marketplace\Helper\Seller $sellerHelper,
            \Knowband\Marketplace\Helper\Log $logHelper,
            \Magento\Framework\View\LayoutFactory $viewLayoutFactory
    ) {
        parent::__construct($context, $request, $response, $storeManager, $registry, $customerSessionModel, $resultRawFactory, $settingHelper, $sellerHelper);
        $this->mp_request = $request;
        $this->mp_storeManager = $storeManager;
        $this->mp_resultRawFactory = $resultRawFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->mp_logHelper = $logHelper;
        $this->_viewLayoutFactory = $viewLayoutFactory;
    }

    public function execute() {
        $json = [];
        $result = $this->resultJsonFactory->create();
        try {
            
            $block = $this->_viewLayoutFactory->create()->createBlock('\Knowband\Marketplace\Block\Product\Attributeselection');
            $json['html'] = $block->toHtml();
            $json['html_title'] = __('Select Attribute Set for New Product');
        } catch (\Exception $ex) {
            $json['error'] = $ex->getMessage();
            $this->mp_logHelper->createFileAndWriteLogData(
                    \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Controller Product/GetAttributesSet::execute()', $ex->getMessage()
            );
        }
        return $result->setData($json);
    }
}
