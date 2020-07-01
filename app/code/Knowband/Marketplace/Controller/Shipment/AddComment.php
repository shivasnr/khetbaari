<?php

namespace Knowband\Marketplace\Controller\Shipment;

use Knowband\Marketplace\Controller\Index\ParentController;
class AddComment extends ParentController {

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
            \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
            \Knowband\Marketplace\Model\Seller $selleModel,
            \Magento\Sales\Model\OrderFactory $orderFactory,
            \Magento\Sales\Model\Order\Email\Sender\ShipmentCommentSender $shipmentCommentSender,
            \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader $shipmentLoader
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
        $this->shipmentCommentSender = $shipmentCommentSender;
        $this->shipmentLoader = $shipmentLoader;
    }

    public function execute() {
        $this->isLoggedIn();

        try {
            $this->getRequest()->setParam('shipment_id', $this->getRequest()->getParam('id'));
            $data = $this->getRequest()->getPost('comment');
            if (empty($data['comment'])) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Please enter a comment.')
                );
            }
            $this->shipmentLoader->setOrderId($this->getRequest()->getParam('order_id'));
            $this->shipmentLoader->setShipmentId($this->getRequest()->getParam('shipment_id'));
            $this->shipmentLoader->setShipment($this->getRequest()->getParam('shipment'));
            $this->shipmentLoader->setTracking($this->getRequest()->getParam('tracking'));
            $shipment = $this->shipmentLoader->load();
            $shipment->addComment(
                $data['comment'],
                isset($data['is_customer_notified']),
                isset($data['is_visible_on_front'])
            );

            $this->shipmentCommentSender->send($shipment, !empty($data['is_customer_notified']), $data['comment']);
            $shipment->save();
            $this->messageManager->addSuccess(__('Comment has been added for this shipment.'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addError(__('Cannot add new comment.'));
        }
        
        $this->_redirect('*/*/view', [
            'shipment_id' => $this->getRequest()->getParam('shipment_id')
        ]);
    }

}
