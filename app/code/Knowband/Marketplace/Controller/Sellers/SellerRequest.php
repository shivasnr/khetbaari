<?php

namespace Knowband\Marketplace\Controller\Sellers;

use Knowband\Marketplace\Controller\Index\ParentController;
use Magento\Framework\Controller\ResultFactory;
class SellerRequest extends ParentController {

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
            \Knowband\Marketplace\Model\Seller $mpSellerModel,
            \Magento\Framework\Event\Manager $eventManager
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
        $this->customerSessionModel = $customerSessionModel;
        $this->_urlInterface = $context->getUrl();
        $this->_eventManager = $eventManager;
    }

    public function execute() {
        $this->isLoggedIn();
        $seller = $this->_coreRegistry->registry('vssmp_seller_info');
        $seller_id = $seller['entity_id'];
        if(!isset($seller['entity_id']) || !$seller['entity_id']){
            $this->getResponse()->setRedirect($this->_urlInterface->getBaseUrl());
        }
        try {
            $customerData = $this->customerSessionModel->getCustomerData();
            if ($customerData) {
                $customer_data['entity_id'] = $customerData->getId();
                $customer_data['email'] = $customerData->getEmail();
                $customer_data['website_id'] = $customerData->getWebsiteId();
                $customer_data['firstname'] = $customerData->getFirstName();
                $customer_data['lastname'] = $customerData->getLastName();
                $this->_eventManager->dispatch('marketplace_seller_register_before', ['seller_data' => &$customer_data]);
                if($this->mp_dataHelper->saveInitialSellerData($customer_data, true)){
                     $this->messageManager->addSuccess('Your request has been saved successfully.');
                }else{
                     $this->messageManager->addError(__("You can't register yourself as seller because your registration request limit has been over. To register, please contact to support."));
                }
                $this->_eventManager->dispatch('marketplace_seller_register_after', ['seller_data' => $customer_data]);
            } else{
                $this->messageManager->addError(__('Invalid Request'));
                $this->getResponse()->setRedirect($this->_urlInterface->getBaseUrl());
            }
        } catch (\Exception $ex) {
            $this->messageManager->addError($ex->getMessage());
            $this->mp_logHelper->createFileAndWriteLogData(
                \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Controller Sellers\SellerRequest::execute()', '(Seller Id:- ' . $seller_id . ') '.$ex->getMessage()
            );
        }
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }

}
