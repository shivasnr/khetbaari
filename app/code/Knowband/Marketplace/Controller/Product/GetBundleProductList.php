<?php

namespace Knowband\Marketplace\Controller\Product;

use Knowband\Marketplace\Controller\Index\ParentController;
class GetBundleProductList extends ParentController {

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
        $this->_objectManager = $context->getObjectManager();
        $this->_viewLayoutFactory = $viewLayoutFactory;
    }

    public function execute() {
        $json = [];
        $resultJson = $this->resultJsonFactory->create();
        try {
            $dataHelper = $this->_objectManager->get("Knowband\Marketplace\Helper\Data");
            $productHelper = $this->_objectManager->get("Knowband\Marketplace\Helper\Product");
            $post_data = $this->getRequest()->getParams();
            $list_params = json_decode(base64_decode($post_data['list_params']));

            foreach ($list_params as $key => $val) {
                $this->mp_request->setPostValue($key, $val);
            }

            $this->_initProduct();

            $col_order = $dataHelper->getColOrder($post_data);
            $result = $productHelper->getListForBundleProduct($post_data, $col_order, $list_params);
            $data = [];
            foreach ($result['collection'] as $pro) {
                $data[] = [
                    '<input type="checkbox" value="' . $pro->getId() . '" />',
                    $pro->getId(),
                    '<a href="' . $pro->getProductUrl(true) . '" title="click to view detail" target="_blank">' . $pro->getName() . '</a>',
                    $pro->getSku(),
                    $productHelper->getAttributeSetName($pro->getAttributeSetId()),
                    $dataHelper->formatCurrency($pro->getPrice()),
                    $productHelper->getStockStatusTxt($pro->getExtensionAttributes()->getStockItem()->getIsInStock()),
                    '<div class="form-group"><input type="text" name="bundle_entered_qty" value="" class="form-control"></div>'
                ];
            }
            $json = [
                "draw" => intval($post_data['draw']),
                "recordsTotal" => intval($result['count']),
                "recordsFiltered" => intval($result['count']),
                "data" => $data
            ];
            
        } catch (\Exception $ex) {
            $json['error'] = $ex->getMessage();
            $this->mp_logHelper->createFileAndWriteLogData(
                    \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Controller Product/GetBundleProductList::execute()', $ex->getMessage()
            );
        }
        return $resultJson->setData($json);
    }

}
