<?php

namespace Knowband\Marketplace\Controller\Sellers;

use Knowband\Marketplace\Controller\Index\ParentController;
class View extends ParentController {

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
            \Knowband\Marketplace\Helper\Data $mpDataHelper,
            \Knowband\Marketplace\Helper\Log $mpLogHelper,
            \Knowband\Marketplace\Model\Seller $mpSellerModel
    ) {
        parent::__construct($context, $request, $response, $storeManager, $registry, $customerSessionModel, $resultRawFactory, $settingHelper, $sellerHelper);
        $this->mp_request = $request;
        $this->_coreRegistry = $registry;
        $this->mp_storeManager = $storeManager;
        $this->mp_resultRawFactory = $resultRawFactory;
        $this->mp_settingHelper = $settingHelper;
        $this->mp_dataHelper = $mpDataHelper;
        $this->mp_logHelper = $mpLogHelper;
        $this->mp_sellerModel = $mpSellerModel;
        $this->_urlInterface = $this->_objectManager->get('Magento\Framework\UrlInterface');
    }

    public function execute() {
        $resultPage = $this->mp_resultRawFactory->create();
        if (!$this->getRequest()->getParam('id')) {
            $this->getResponse()->setRedirect($this->_urlInterface->getBaseUrl());
        }
        $seller_id = $this->getRequest()->getParam('id');
        try {
            $model = $this->mp_sellerModel->load($seller_id, 'seller_id');
            $loginPageUrl = $this->_urlInterface->getUrl('customer/account');
            if ($model->getData()) {
                $sellerData = $model->getData();
                $this->_coreRegistry->register('sellerDetail', $sellerData);
                $this->_coreRegistry->register('loginPageUrl', $loginPageUrl);

                $allowedCategories = $this->mp_settingHelper->getSettingByKey($sellerData['seller_id'], 'category_ids');
                if (isset($allowedCategories['seller'])){
                    $allowedCategories = $allowedCategories['seller'];
                }

                $allowedCategories = explode(',', $allowedCategories);

                $cat_array = $this->mp_dataHelper->getCategoriesArray();
                $final_cat_array = [];
                if (empty($allowedCategories) || empty($allowedCategories[0])){
                    $this->_coreRegistry->register('categories', $cat_array);
                }
                else {
                    foreach ($cat_array as $cat) {
                        if (!in_array($cat['id'], $allowedCategories)){
                            continue;
                        }
                        $final_cat_array[] = $cat;
                    }
                    $this->_coreRegistry->register('categories', $final_cat_array);
                }
                unset($cat_array);
                if (empty($sellerData['shop_title'])){
                    $resultPage->getConfig()->getTitle()->prepend(__("Not Available"));
                }
                else{
                    $resultPage->getConfig()->getTitle()->prepend($sellerData['shop_title']);
                }
            } else{
                $this->getResponse()->setRedirect($this->_urlInterface->getBaseUrl());
            }
        } catch (\Exception $ex) {
            $this->messageManager->addError($ex->getMessage());
            $this->mp_logHelper->createFileAndWriteLogData(
                \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Controller Sellers\View::execute()', '(Seller Id:- ' . $seller_id . ') '.$ex->getMessage()
            );
        }
        return $resultPage;
    }

}
