<?php

namespace Knowband\Marketplace\Controller\Product;

use Knowband\Marketplace\Controller\Index\ParentController;
//use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper;

class Validate extends ParentController {

    protected $mp_resultRawFactory;
    protected $mp_request;
    protected $mp_scopeConfig;
    protected $inlineTranslation;
    protected $mp_transportBuilder;

    protected $initializationHelper;
    
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
            \Magento\Catalog\Model\Product\Validator $productValidator,
            \Magento\Catalog\Model\ProductFactory $productFactory
    ) {
        parent::__construct($context, $request, $response, $storeManager, $registry, $customerSessionModel, $resultRawFactory, $settingHelper, $sellerHelper);
        $this->mp_request = $request;
        $this->mp_storeManager = $storeManager;
        $this->mp_resultRawFactory = $resultRawFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->mp_logHelper = $logHelper;
        $this->_objectManager = $context->getObjectManager();
        $this->_viewLayoutFactory = $viewLayoutFactory;
        $this->productValidator = $productValidator;
        $this->productFactory = $productFactory;
    }

    public function execute() {
        $response = new \Magento\Framework\DataObject();
        $response->setError(false);

        try {
            $postedData = $this->getRequest()->getPost();
            $data = $this->_objectManager->get("Knowband\Marketplace\Helper\Product")->processDataBeforeSave($postedData, false);
            $this->getRequest()->setPostValue('product', $data);
            
            $productData = $this->getRequest()->getPost('product', []);

            if ($productData && !isset($productData['stock_data']['use_config_manage_stock'])) {
                $productData['stock_data']['use_config_manage_stock'] = 0;
            }
            $storeId = $this->getRequest()->getParam('store', 0);
            $store = $this->mp_storeManager->getStore($storeId);
            $this->mp_storeManager->setCurrentStore($store->getCode());
            /* @var $product \Magento\Catalog\Model\Product */
            $product = $this->productFactory->create();
            $product->setData('_edit_mode', true);
            if ($storeId) {
                $product->setStoreId($storeId);
            }
            $setId = $this->getRequest()->getPost('set') ?: $this->getRequest()->getParam('set');
            if ($setId) {
                $product->setAttributeSetId($setId);
            }
            $typeId = $this->getRequest()->getParam('type');
            if ($typeId) {
                $product->setTypeId($typeId);
            }
            $productId = $this->getRequest()->getParam('id');
            if ($productId) {
                $product->load($productId);
            }
            $product = $this->getInitializationHelper()->initializeFromData($product, $productData);

            /* set restrictions for date ranges */
            $resource = $product->getResource();
            $resource->getAttribute('special_from_date')->setMaxValue($product->getSpecialToDate());
            $resource->getAttribute('news_from_date')->setMaxValue($product->getNewsToDate());
            $resource->getAttribute('custom_design_from')->setMaxValue($product->getCustomDesignTo());

            $this->productValidator->validate($product, $this->getRequest(), $response);
        } catch (\Magento\Eav\Model\Entity\Attribute\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            $response->setError(true);
            $response->setAttribute($e->getAttributeCode());
            $response->setMessages([$e->getMessage()]);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
            $response->setError(true);
            $response->setMessages([$e->getMessage()]);
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            $layout = $this->_viewLayoutFactory->create();
            $layout->initMessages();
            $response->setError(true);
            $response->setHtmlMessage($layout->getMessagesBlock()->getGroupedHtml());
        }

        return $this->resultJsonFactory->create()->setData($response);
    }
    
    /**
     * @return Initialization\Helper
     * @deprecated
     */
    protected function getInitializationHelper()
    {
        if (null === $this->initializationHelper) {
            $this->initializationHelper = $this->_objectManager->get(\Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper::class);
        }
        return $this->initializationHelper;
    }

}
