<?php

namespace Knowband\Marketplace\Controller\Adminhtml\Marketplace;
use Magento\Framework\View\LayoutFactory;
class DeleteProduct extends \Magento\Backend\App\Action
{
    public function __construct(
            \Magento\Backend\App\Action\Context $context,
            LayoutFactory $viewLayoutFactory,
            \Magento\Framework\Registry $registry,
            \Magento\Framework\Event\Manager $eventManager,
            \Magento\Catalog\Model\Product $product,
            \Knowband\Marketplace\Model\Product $mpProductToSellerModel,
            \Knowband\Marketplace\Helper\Product $mpProductHelper,
            \Knowband\Marketplace\Helper\Email $mpEmailHelper,
            \Knowband\Marketplace\Helper\Log $mpLogHelper
    )
    {
        $this->_coreRegistry = $registry;
        $this->_viewLayoutFactory = $viewLayoutFactory;
        $this->_productModel = $product;
        $this->mp_productToSellerModel = $mpProductToSellerModel;
        $this->mp_productHelper = $mpProductHelper;
        $this->mp_emailHelper = $mpEmailHelper;
        $this->mp_logHelper = $mpLogHelper;
        $this->_eventManager = $eventManager;
        parent::__construct($context);
    }

    public function execute() {
        $postData = $this->getRequest()->getParams();
        $success_msg = '';
        try {
            if (isset($postData['reason_txt'])) {
                $reason_txt = $postData['reason_txt'];
                $ids = $postData['custom_id']; // sellerId_productId
                if (!is_array($ids)) {
                    $custom_ids[] = $ids;
                } else {
                    $custom_ids = $ids;
                }
                $succes_count = 0;
                foreach ($custom_ids as $custom_id) {
                    $ids = explode('_', $custom_id);
                    $product = $this->_productModel->load($ids[1]);
                    if ($product) {
                        $this->_eventManager->dispatch("catalog_controller_product_delete", ['product' => $product]);
                        try {
                            $product->delete();
                        } catch (\Exception $ex) {
                            $message = 'Failed to delete product(#' . $ids[1] . ') of seller(#' . $ids[0] . ') by admin. Error - ' . $ex->getMessage();
                            $this->messageManager->addErrorMessage($message);
                            $this->mp_logHelper->createFileAndWriteLogData(
                                    \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Controller DeleteProduct::execute()', $message
                            );
                        }
                        $mapping = $this->mp_productToSellerModel->getCollection();
                        $mapping->addFieldToFilter('seller_id', ['eq' => $ids[0]]);
                        $mapping->addFieldToFilter('product_id', ['eq' => $ids[1]]);
                        $data = $mapping->getData();
                        unset($mapping);
                        if (!empty($data)) {
                            try {
                                $remove_mapping = $this->mp_productToSellerModel->load($data[0]['seller_product_id']);
                                $remove_mapping->delete();
                                $remove_mapping->unsetData();
                            } catch (\Exception $e) {
                                $message = 'Failed to delete product(#' . $ids[0] . ') mapping with seller(#' . $ids[0] . ') after deleting product by admin. Error - ' . $e->getMessage();
                                $this->mp_logHelper->createFileAndWriteLogData(
                                        \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Controller DeleteProduct::execute()', $message
                                );
                            }
                        }
                        $this->mp_emailHelper->sendDeleteProductEmailtoSeller($ids[0], $ids[1], $reason_txt);
                        $succes_count++;
                    }
                    $product->unsetData();
                }

                $success_msg = sprintf(__('Total of %d record(s) have been deleted.'), ($succes_count));

                $this->messageManager->addSuccessMessage($success_msg);
                $this->_redirect('mpadmin/marketplace/sellerProductList');
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__("Some error occured. Please try again.") . $e->getMessage());
            $this->_redirect('mpadmin/marketplace/sellerProductList');
        }

        $this->_coreRegistry->unregister("reason_submit_action");
        $this->_coreRegistry->register("reason_submit_action", $this->getUrl('mpadmin/marketplace/deleteProduct'));
        $itemsBlock = $this->_viewLayoutFactory->create()->createBlock('\Knowband\Marketplace\Block\Adminhtml\FormBlock');
        $output = $itemsBlock->setTemplate('Knowband_Marketplace::reason_popup.phtml')->toHtml();
        $this->getResponse()->appendBody($output);
    }

}
