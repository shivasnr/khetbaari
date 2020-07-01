<?php

namespace Hariyo\Marketplace\Observer;

use Magento\Framework\Event\ObserverInterface;

class SaveAsSeller implements ObserverInterface {

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
            \Magento\Framework\Session\SessionManagerInterface $sessionManager, 
            \Magento\Framework\Message\ManagerInterface $messageManager,
            \Psr\Log\LoggerInterface $logger,
            \Magento\Framework\App\Request\Http $request,
            \Magento\Framework\Event\Manager $eventManager,
            \Hariyo\Marketplace\Helper\Log $mpLogHelper,
            \Hariyo\Marketplace\Helper\Data $mpDataHelper
    ) {
        $this->_sessionManager = $sessionManager;
        $this->_logger = $logger;
        $this->mp_request = $request;
        $this->mp_logHelper = $mpLogHelper;
        $this->mp_dataHelper = $mpDataHelper;
        $this->_eventManager = $eventManager;
        $this->messageManager = $messageManager;
    }

    /**
     * customer register event handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer) {
        try {
            if($this->_sessionManager->getRegisterAsSeller()){
                $seller_count = $this->mp_dataHelper->getSellersCount();
                if ($seller_count <= \Hariyo\Marketplace\Helper\Seller::SELLER_REGISTER_ALLOWED) {
                    $customer = $observer->getEvent()->getCustomer();
                    $customer_data = [];
                    $customer_data['entity_id'] = $customer->getId();
                    $customer_data['website_id'] = $customer->getWebsiteId();
                    ;
                    $customer_data['email'] = $customer->getEmail();
                    $customer_data['firstname'] = $customer->getFirstName();
                    $customer_data['lastname'] = $customer->getLastName();
                    $this->_eventManager->dispatch('marketplace_seller_register_before', ['seller_data' => &$customer_data]);
                    $this->mp_dataHelper->saveInitialSellerData($customer_data, true);
                    $this->_eventManager->dispatch('marketplace_seller_register_after', ['seller_data' => $customer_data]);
                } else {
                    $this->messageManager->addError(__('You cannot register yourself as seller. Please contact the admin for more details.'));
                }
            }
            $this->_sessionManager->unsRegisterAsSeller();
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            // $this->mp_logHelper->createFileAndWriteLogData(
            //         \Hariyo\Marketplace\Helper\Log::INFOTYPEERROR, 'Observer SaveAsSeller::execute()', $e->getMessage()
            // );
        }
    }
}
