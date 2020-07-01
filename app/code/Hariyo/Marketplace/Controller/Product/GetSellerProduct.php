<?php

namespace Hariyo\Marketplace\Controller\Product;

use Hariyo\Marketplace\Controller\Index\ParentController;
class GetSellerProduct extends ParentController {

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
            \Hariyo\Marketplace\Helper\Setting $settingHelper,
            \Hariyo\Marketplace\Helper\Seller $sellerHelper,
            \Magento\Framework\View\LayoutInterface $layout
    ) {
        parent::__construct($context, $request, $response, $storeManager, $registry, $customerSessionModel, $resultRawFactory, $settingHelper, $sellerHelper);
        $this->mp_request = $request;
        $this->mp_storeManager = $storeManager;
        $this->mp_resultRawFactory = $resultRawFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_layout = $layout;
    }

    public function execute() {
        $json = [];
        $result = $this->resultJsonFactory->create();
        try {
            $data = $this->_layout->getBlockSingleton(\Hariyo\Marketplace\Block\Product\ProductList::class)->getSellerProduct();
            $json = [
                "draw" => intval($this->getRequest()->getParam('draw')),
                "recordsTotal" => intval($data['count']),
                "recordsFiltered" => intval($data['count']),
                "data" => $data['collection']
            ];
        } catch (\Exception $ex) {
            $json['error'] = $ex->getMessage();
            $this->_logger->info($json['error']);
        }
        return $result->setData($json);
    }
}
