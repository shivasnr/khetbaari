<?php

namespace Knowband\Marketplace\Controller\Product;

use Knowband\Marketplace\Controller\Index\ParentController;
class ValidateSku extends ParentController {

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
            \Magento\Framework\View\LayoutFactory $viewLayoutFactory,
            \Magento\Catalog\Model\Product $productModel
    ) {
        parent::__construct($context, $request, $response, $storeManager, $registry, $customerSessionModel, $resultRawFactory, $settingHelper, $sellerHelper);
        $this->mp_request = $request;
        $this->mp_storeManager = $storeManager;
        $this->mp_resultRawFactory = $resultRawFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->mp_logHelper = $logHelper;
        $this->_objectManager = $context->getObjectManager();
        $this->_viewLayoutFactory = $viewLayoutFactory;
        $this->_productModel = $productModel;
    }

    public function execute() {
        $json = [];
        $result = $this->resultJsonFactory->create();
        try {
            $found = false;
            $product_id = (int) $this->getRequest()->getParam('product_id');
            $sku = $this->getRequest()->getParam('sku');
            if ($product_id) {
                $product = $this->_productModel->load($product_id);
                $orgin_sku = $product->getSku();
                if ($orgin_sku != $sku) {
                    if ($this->_productModel->getIdBySku($sku)) {
                        $found = true;
                    }
                }
                $product->unsetData();
            } else {
                if ($this->_productModel->getIdBySku($sku)) {
                    $found = true;
                }
            }

            $json = [];
            if ($found) {
                $json['error'] = __('This SKU already exist.');
            } else {
                $json['success'] = 1;
            }
        } catch (\Exception $ex) {
            $json['error'] = $ex->getMessage();
            $this->mp_logHelper->createFileAndWriteLogData(
                    \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Controller Product/ValidateSku::execute()', $ex->getMessage()
            );
        }
        return $result->setData($json);
    }

}
