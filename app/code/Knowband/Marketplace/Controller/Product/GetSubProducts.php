<?php

namespace Knowband\Marketplace\Controller\Product;

use Knowband\Marketplace\Controller\Index\ParentController;
class GetSubProducts extends ParentController {

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
        $data = [];
        $resultJson = $this->resultJsonFactory->create();
        try {
            $dataHelper = $this->_objectManager->get("Knowband\Marketplace\Helper\Data");
            $productHelper = $this->_objectManager->get("Knowband\Marketplace\Helper\Product");
            $post_data = $this->getRequest()->getParams();
            $list_params = json_decode(base64_decode($post_data['list_params']));

            foreach ($list_params as $key => $val) {
                $this->mp_request->setPostValue($key, $val);
            }

            $product = $this->_initProduct();

            $col_order = $dataHelper->getColOrder($post_data);

            if ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                $result = $productHelper->getSubProductList($post_data, $col_order, $list_params);
                $checked_products = (array) json_decode($post_data['checked_products']);
                foreach ($result['collection'] as $pro) {
                    $checked = '';
                    if (in_array($pro->getId(), $checked_products)) {
                        $checked = 'checked="checked"';
                    }
                    $data[] = [
                        '<input id="associate_' . $pro->getId() . '" type="checkbox" name="sub_products[' . $pro->getId() . ']" value=\'' . htmlspecialchars($productHelper->getSubProAttributesJson($pro)) . '\' ' . $checked . '  onclick="vssmpProcessSubSelect(this)"/>',
                        $pro->getId(),
                        '<a href="' . $pro->getProductUrl(true) . '" title="click to view detail" target="_blank">' . $pro->getName() . '</a>',
                        $pro->getSku(),
                        $productHelper->getAttributeSetName($pro->getAttributeSetId()),
                        $dataHelper->formatCurrency($pro->getPrice()),
                        $productHelper->getStockStatusTxt($pro->getExtensionAttributes()->getStockItem()->getIsInStock())
                    ];
                }
            } else if ($product->getTypeId() == \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE) {
                $result = $productHelper->getListForGroupProduct($post_data, $col_order, $list_params);

                $checked_products = (array) json_decode($post_data['checked_products']); //print_r($checked_products);
                foreach ($result['collection'] as $pro) {
                    $checked = '';
                    $qty = 0;
                    foreach ($checked_products as $key => $val) {
                        if ($pro->getId() == $key) {
                            $checked = 'checked="checked"';
                            $qty = $val->qty;
                            break;
                        }
                    }

                    $data[] = [
                        '<input type="checkbox" name="sub_products[]" value="' . $pro->getId() . '" ' . $checked . ' onclick="vssmpProcessSubSelect(this)"/>',
                        $pro->getId(),
                        '<a href="' . $pro->getProductUrl(true) . '" title="click to view detail" target="_blank">' . $pro->getName() . '</a>',
                        $pro->getSku(),
                        $productHelper->getAttributeSetName($pro->getAttributeSetId()),
                        $dataHelper->formatCurrency($pro->getPrice()),
                        $productHelper->getStockStatusTxt($pro->getExtensionAttributes()->getStockItem()->getIsInStock()),
                        '<input type="text" class="vssmp-in-dis input-width40" onchange="vssmpProcessSubProductQtyChange(this)" onkeyup="vssmpProcessSubProductQtyChange(this)" name="group_pro_qty[]" value="' . $qty . '" />'
                    ];
                }
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
                    \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Controller Product/GetSubProducts::execute()', $ex->getMessage()
            );
        }
        return $resultJson->setData($json);
    }

}
