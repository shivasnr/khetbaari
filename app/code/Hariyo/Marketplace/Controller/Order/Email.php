<?php

namespace Hariyo\Marketplace\Controller\Order;

use Hariyo\Marketplace\Controller\Index\ParentController;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\InputException;
class Email extends ParentController {

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
            \Magento\Sales\Api\OrderManagementInterface $orderManagement,
            \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
        parent::__construct($context, $request, $response, $storeManager, $registry, $customerSessionModel, $resultRawFactory, $settingHelper, $sellerHelper);
        $this->mp_request = $request;
        $this->_coreRegistry = $registry;
        $this->mp_storeManager = $storeManager;
        $this->mp_resultRawFactory = $resultRawFactory;
        $this->mp_settingHelper = $settingHelper;
        $this->mp_sellerModel = $selleModel;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderFactory = $orderFactory;
        $this->orderManagement = $orderManagement;
    }

    public function execute() {
        $this->isLoggedIn();
        $id = $this->getRequest()->getParam('order_id');
        try {
            $order = $this->orderFactory->create()->load($id);
            if($order){
                $this->_coreRegistry->register('sales_order', $order);
                $this->_coreRegistry->register('current_order', $order);
                try {
                    $this->orderManagement->notify($order->getEntityId());
                    $this->messageManager->addSuccess(__('You sent the order email.'));
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->messageManager->addError($e->getMessage());
                } catch (\Exception $e) {
                    $this->messageManager->addError(__('We can\'t send the email order right now.'));
                    $this->logger->critical($e);
                }
                return $this->resultRedirectFactory->create()->setPath(
                                'marketplace/order/orderview', [
                            'order_id' => $order->getEntityId()
                                ]
                );
            }
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addError(__('This order no longer exists.'));
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        } catch (InputException $e) {
            $this->messageManager->addError(__('This order no longer exists.'));
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }

        return $this->resultRedirectFactory->create()->setPath('marketplace/order/orderlist');
    }
}
