<?php

namespace Hariyo\Marketplace\Observer;

use Magento\Framework\Event\ObserverInterface;

class ProcessOnNewOrder implements ObserverInterface {

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
            \Magento\Framework\Session\SessionManagerInterface $sessionManager, 
            \Magento\Store\Model\StoreManagerInterface $storeManager,
            \Magento\Sales\Api\OrderRepositoryInterface $orderRepository, 
            \Magento\Framework\Registry $registry,
            \Psr\Log\LoggerInterface $logger, 
            \Magento\Framework\Event\Manager $eventManager,
            \Magento\Framework\App\Request\Http $request, 
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Hariyo\Marketplace\Model\Seller $mpSellerModel,
            \Hariyo\Marketplace\Helper\Reports $mpReportsHelper,
            \Hariyo\Marketplace\Helper\Setting $mpSettingHelper,
            \Hariyo\Marketplace\Helper\Product $mpProductHelper,
            \Hariyo\Marketplace\Helper\Email $mpEmailHelper,
            \Hariyo\Marketplace\Helper\Log $mpLogHelper
    ) {
        $this->_sessionManager = $sessionManager;
        $this->_logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->_storeManager = $storeManager;
        $this->_registry = $registry;
        $this->_eventManager = $eventManager;
        $this->mp_request = $request;
        $this->mp_sellerModel = $mpSellerModel;
        $this->mp_logHelper = $mpLogHelper;
        $this->mp_productHelper = $mpProductHelper;
        $this->mp_settingHelper = $mpSettingHelper;
        $this->mp_emailHelper = $mpEmailHelper;
        $this->mp_reportsHelper = $mpReportsHelper;
        $this->_objectManager = $objectManager;
    }

    /**
     * customer register event handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer) {
        try {
            $settings = $this->mp_settingHelper->getSettings();
            if (isset($settings['enable_mp']) && $settings['enable_mp'] == 1) {
                $event = $observer->getEvent();
                $orderIds = $event->getOrderIds();
                $order_id = $orderIds[0];
                $order = $this->orderRepository->get($order_id);

                $storeId = $this->_storeManager->getStore()->getId();
                $seller_products = $this->mp_reportsHelper->getSellerProductFromOrder($order->getId());
                if ($seller_products && $this->_objectManager->get("\Magento\Sales\Helper\Data")->canSendNewOrderEmail($storeId)) {
                    $paymentBlockHtml = '';
                    try {
                        // Retrieve specified view block from appropriate design package (depends on emulated store)
                        $paymentBlock = $this->_objectManager->get("\Magento\Payment\Helper\Data")->getInfoBlock($order->getPayment())
                                ->setIsSecureMode(true);
                        $paymentBlock->getMethod()->setStore($storeId);
                        $paymentBlockHtml = $paymentBlock->toHtml();
                    } catch (\Exception $exception) {
                        throw $exception;
                    }

                    $emailTemplateVariables = [];
                    $emailTemplateVariables['order'] = $order;
                    $emailTemplateVariables['billing'] = $order->getBillingAddress();
                    $emailTemplateVariables['payment_html'] = $paymentBlockHtml;

                    foreach ($seller_products as $seller_id => $items) {
                        $is_new = true;
                        $order_id = $order->getId();

                        $check_for_edited = $this->_objectManager->create("\Magento\Sales\Model\Order")->load($order->getOriginalIncrementId(), 'increment_id');
                        if (!empty($check_for_edited->getData())) {
                            $is_new = false;
                            $order_id = $check_for_edited->getId();
                        }
                        if ($is_new) {
                            try {
                                $seller_items = [];
                                foreach ($items as $item) {
                                    $seller_items[] = $item['product_id'];
                                }
                                $this->_registry->unregister("seller_items");
                                $this->_registry->unregister("order_email_current_seller_id");
                                $this->_registry->unregister("current_order");

                                $this->_registry->register('seller_items', $seller_items);
                                $this->_registry->register('order_email_current_seller_id', $seller_id);
                                $this->_registry->register('current_order', $order);

                                $this->mp_emailHelper->sendOrderNotificationToSeller($seller_id, $emailTemplateVariables);
                            } catch (\Exception $e) {
                                $message = 'Failed to send email to seller(#' . $seller_id . ') for order(#' . $order_id . '). Error - ' . $e->getMessage();
                                $this->mp_logHelper->createFileAndWriteLogData(
                                        \Hariyo\Marketplace\Helper\Log::INFOTYPEERROR, 'Observer ProcessOnNewOrder::execute()', $message
                                );
                            }
                        }
                        //Calculate and save seller earnings and admin commission
                        $this->mp_reportsHelper->saveEarningAndCommission($seller_id, $order_id, $items, $is_new);
                        $this->mp_productHelper->saveSellerOrderItem($seller_id, $order->getId(), $items, $is_new);

                        if ($is_new) {
                            $this->_eventManager->dispatch(
                                    'seller_orderitem_save_after', ['items' => $items, 'order' => $order, 'seller_id' => $seller_id]
                            );
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            $this->mp_logHelper->createFileAndWriteLogData(
                    \Hariyo\Marketplace\Helper\Log::INFOTYPEERROR, 'Observer ProcessOnNewOrder::execute()', $e->getMessage()
            );
        }
    }

}
