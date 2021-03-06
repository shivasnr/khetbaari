<?php

namespace Hariyo\Marketplace\Controller\Order;

use Hariyo\Marketplace\Controller\Index\ParentController;
class OrderView extends ParentController {

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
            \Hariyo\Marketplace\Helper\Setting $settingHelper,
            \Hariyo\Marketplace\Helper\Seller $sellerHelper,
            \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
            \Hariyo\Marketplace\Model\Seller $selleModel,
            \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
        parent::__construct($context, $request, $response, $storeManager, $registry, $customerSessionModel, $resultRawFactory, $settingHelper, $sellerHelper);
        $this->mp_request = $request;
        $this->_registry = $registry;
        $this->mp_storeManager = $storeManager;
        $this->mp_resultRawFactory = $resultRawFactory;
        $this->mp_settingHelper = $settingHelper;
        $this->mp_sellerModel = $selleModel;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderFactory = $orderFactory;
    }

    public function execute() {
        $this->isLoggedIn();
//        $this->isValidOrder();
        $order_id = $this->getRequest()->getParam('order_id');
        $order = $this->orderFactory->create()->load($order_id);
        if ($order->getId()) {
            $this->_registry->register('current_order', $order);
            $resultPage = $this->mp_resultRawFactory->create();
            $resultPage->getConfig()->getTitle()->prepend(__("Seller OrderView"));
            return $resultPage;
        } else {
            $this->messageManager->addError(__('Requested order details not Found.'));
            $this->_redirect('marketplace/order/orderlist');
        }
    }
}
