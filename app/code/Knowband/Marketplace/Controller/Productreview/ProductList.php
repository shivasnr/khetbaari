<?php

namespace Knowband\Marketplace\Controller\Productreview;

use Knowband\Marketplace\Controller\Index\ParentController;
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
            \Knowband\Marketplace\Helper\Setting $settingHelper,
            \Knowband\Marketplace\Helper\Seller $sellerHelper,
            \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
            \Magento\Framework\View\LayoutInterface $layout
    ) {
        parent::__construct($context, $request, $response, $storeManager, $registry, $customerSessionModel, $resultRawFactory, $settingHelper, $sellerHelper);
        $this->mp_request = $request;
        $this->mp_storeManager = $storeManager;
        $this->mp_resultRawFactory = $resultRawFactory;
        $this->mp_settingHelper = $settingHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_layout = $layout;
    }

    public function execute() {
        
        $this->isLoggedIn();
        $resultPage = $this->mp_resultRawFactory->create();
        if($this->mp_request->getParam('isAjax') && $this->mp_request->getParam('isAjax') == true){
            $data = $this->_layout->getBlockSingleton(\Knowband\Marketplace\Block\Review\Product\ProductList::class)->getReviewList();
            $json = [
                "draw" => intval($this->mp_request->getParam('draw')),
                "recordsTotal" => intval($data['count']),
                "recordsFiltered" => intval($data['count']),
                "data" => $data['collection']
            ];
            $result = $this->resultJsonFactory->create();
            return $result->setData($json);
        }
        $resultPage->getConfig()->getTitle()->prepend(__("Seller's Products - Reviews"));
        return $resultPage;
    }
}
