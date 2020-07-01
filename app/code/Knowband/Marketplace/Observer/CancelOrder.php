<?php

namespace Knowband\Marketplace\Observer;

use Magento\Framework\Event\ObserverInterface;

class CancelOrder implements ObserverInterface {

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
            \Psr\Log\LoggerInterface $logger,
            \Magento\Sales\Api\OrderRepositoryInterface $orderRepository, 
            \Knowband\Marketplace\Helper\Reports $mpReportsHelper,
            \Knowband\Marketplace\Helper\Log $mpLogHelper
    ) {
        $this->orderRepository = $orderRepository;
        $this->mp_reportsHelper = $mpReportsHelper;
        $this->_logger = $logger;
        $this->mp_logHelper = $mpLogHelper;
    }

    /**
     * customer register event handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer) {
        try {
            $event = $observer->getEvent();
            $orderIds = $event->getOrderIds();
            $order_id = $orderIds[0];
            $order = $this->orderRepository->get($order_id);
            $this->mp_reportsHelper->updateEarningOnCancelOrder($order->getId());
            $this->mp_reportsHelper->updateOrderItemsOnCancelOrder($order->s());
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            $this->mp_logHelper->createFileAndWriteLogData(
                    \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Observer CancelOrder::execute()', $e->getMessage()
            );
        }
    }
}
