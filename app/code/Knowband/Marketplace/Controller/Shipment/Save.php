<?php

namespace Knowband\Marketplace\Controller\Shipment;

use Knowband\Marketplace\Controller\Index\ParentController;
use Magento\Sales\Model\Order\Shipment\Validation\QuantityValidator;
class Save extends ParentController {

    protected $mp_resultRawFactory;
    protected $mp_request;
    protected $mp_scopeConfig;
    protected $inlineTranslation;
    protected $mp_transportBuilder;
    
    /**
     * @var \Magento\Sales\Model\Order\Shipment\ShipmentValidatorInterface
     */
    private $shipmentValidator;

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
            \Knowband\Marketplace\Model\Shipments $mpShipmentModel,
            \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader $shipmentLoader,
            \Magento\Framework\Session\SessionManagerInterface $coreSession,
            \Magento\Shipping\Model\Shipping\LabelGenerator $labelGenerator,
            \Magento\Sales\Model\Order\Email\Sender\ShipmentSender $shipmentSender
    ) {
        parent::__construct($context, $request, $response, $storeManager, $registry, $customerSessionModel, $resultRawFactory, $settingHelper, $sellerHelper);
        $this->mp_request = $request;
        $this->_registry = $registry;
        $this->mp_storeManager = $storeManager;
        $this->mp_resultRawFactory = $resultRawFactory;
        $this->mp_settingHelper = $settingHelper;
        $this->mp_sellerHelper = $sellerHelper;
        $this->mp_shipmentModel = $mpShipmentModel;
        $this->_coreSession = $coreSession;
        $this->shipmentLoader = $shipmentLoader;
        $this->labelGenerator = $labelGenerator;
        $this->shipmentSender = $shipmentSender;
    }
    
    /**
     * Save shipment and order in one transaction
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return $this
     */
    protected function _saveShipment($shipment)
    {
        $shipment->getOrder()->setIsInProcess(true);
        $transaction = $this->_objectManager->create(
            'Magento\Framework\DB\Transaction'
        );
        $transaction->addObject(
            $shipment
        )->addObject(
            $shipment->getOrder()
        )->save();

        return $this;
    }

    public function execute() {
        $this->isLoggedIn();
        
        $resultRedirect = $this->resultRedirectFactory->create();
        $isPost = $this->getRequest()->isPost();
        if (!$isPost) {
            $this->messageManager->addError(__('We can\'t save the shipment right now.'));
            return $resultRedirect->setPath('marketplace/order/orderlist');
        }
        
        $data = $this->getRequest()->getParam('shipment');
        
        if (!empty($data['comment_text'])) {
            $this->_coreSession->setCommentText($data['comment_text']);
        }
        
        $isNeedCreateLabel = isset($data['create_shipping_label']) && $data['create_shipping_label'];
        
        try {
            $this->shipmentLoader->setOrderId($this->getRequest()->getParam('order_id'));
            $this->shipmentLoader->setShipmentId($this->getRequest()->getParam('shipment_id'));
            $this->shipmentLoader->setShipment($data);
//            $this->shipmentLoader->setTracking($this->getRequest()->getParam('tracking'));
            $shipment = $this->shipmentLoader->load();
            if (!$shipment) {
                $this->_forward('noroute');
                return;
            }
            
            if (!empty($data['comment_text'])) {
                $shipment->addComment(
                    $data['comment_text'],
                    isset($data['comment_customer_notify']),
                    isset($data['is_visible_on_front'])
                );

                $shipment->setCustomerNote($data['comment_text']);
                $shipment->setCustomerNoteNotify(isset($data['comment_customer_notify']));
            }
            
            $validationResult = $this->getShipmentValidator()
                ->validate($shipment, [QuantityValidator::class]);
            
            if ($validationResult && $validationResult->hasMessages()) {
                $this->messageManager->addError(
                    __("Shipment Document Validation Error(s):\n" . implode("\n", $validationResult->getMessages()))
                );
                $this->_redirect('*/*/createshipment', ['order_id' => $this->getRequest()->getParam('order_id')]);
                return;
            }
            $shipment->register();
            
            $shipment->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
            $responseAjax = new \Magento\Framework\DataObject();

            if ($isNeedCreateLabel) {
                $this->labelGenerator->create($shipment, $this->_request);
                $responseAjax->setOk(true);
            }

            $this->_saveShipment($shipment);
            
            //Map this shipment to seller
            $seller_info = $this->mp_sellerHelper->getSellerInfo();
            $seller_invoice = $this->mp_shipmentModel;
            $seller_invoice->setSellerId((int) $seller_info['entity_id']);
            $seller_invoice->setShipmentId((int) $shipment->getId());
            $seller_invoice->setCreatedAt($this->mp_settingHelper->getDate());
            $seller_invoice->save();
            $seller_invoice->unsetData();

            if (!empty($data['send_email'])) {
                $this->shipmentSender->send($shipment);
            }
            
            $shipmentCreatedMessage = __('The shipment has been created.');
            $labelCreatedMessage = __('You created the shipping label.');

            $this->messageManager->addSuccess(
                $isNeedCreateLabel ? $shipmentCreatedMessage . ' ' . $labelCreatedMessage : $shipmentCreatedMessage
            );
            
            $this->_coreSession->getCommentText(true);
            
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            if ($isNeedCreateLabel) {
                $responseAjax->setError(true);
                $responseAjax->setMessage($e->getMessage());
            } else {
                $this->messageManager->addError($e->getMessage());
                $this->_redirect('*/*/createshipment', ['order_id' => $this->getRequest()->getParam('order_id')]);
            }
        } catch (\Exception $e) {
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
            
            if ($isNeedCreateLabel) {
                $responseAjax->setError(true);
                $responseAjax->setMessage(__('An error occurred while creating shipping label.'));
            } else {
                $this->messageManager->addError(__('Cannot save shipment.'));
                $this->_redirect('*/*/createshipment', ['order_id' => $this->getRequest()->getParam('order_id')]);
            }
        }
        if ($isNeedCreateLabel) {
            $this->getResponse()->representJson($responseAjax->toJson());
        } else {
            $this->_redirect('marketplace/order/orderView', ['order_id' => $shipment->getOrderId()]);
        }
    }
    
    /**
     * @return \Magento\Sales\Model\Order\Shipment\ShipmentValidatorInterface
     * @deprecated
     */
    private function getShipmentValidator()
    {
        if ($this->shipmentValidator === null) {
            $this->shipmentValidator = $this->_objectManager->get(
                \Magento\Sales\Model\Order\Shipment\ShipmentValidatorInterface::class
            );
        }

        return $this->shipmentValidator;
    }
}
