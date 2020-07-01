<?php

namespace Hariyo\Marketplace\Observer;

use Magento\Framework\Event\ObserverInterface;

class RedirectToDashboard implements ObserverInterface {

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
            \Magento\Customer\Model\Session $session,
            \Psr\Log\LoggerInterface $logger,
            \Magento\Framework\UrlInterface $url,
            \Magento\Framework\App\Request\Http $request,
            \Magento\Framework\Event\Manager $eventManager,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Psr\Log\LoggerInterface $mpLogHelper,
            \Hariyo\Marketplace\Helper\Seller $mpSellerHelper
    ) {
        $this->_session = $session;
        $this->_logger = $logger;
        $this->mp_request = $request;
        $this->mp_logHelper = $mpLogHelper;
        $this->mp_sellerHelper = $mpSellerHelper;
        $this->_eventManager = $eventManager;
        $this->_objectManager = $objectManager;
        $this->_url = $url;
    }

    /**
     * customer register event handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer) {
        try {
            
//            $customer = $observer->getEvent()->getCustomer();
//            $customer_data = $customer->getData();
//            $is_seller = $this->mp_sellerHelper->isSeller($customer_data['entity_id']);
            
            $isCustomerLoggedIn = $this->_session->isLoggedIn();
            
            if ($isCustomerLoggedIn) {
                
                $customer = $observer->getEvent()->getCustomer();
                $is_seller = $this->mp_sellerHelper->isSeller($customer->getId());
                $settings_helper = $this->_objectManager->get("Hariyo\Marketplace\Helper\Setting");
                $settings = $settings_helper->getSettings();
                if($is_seller && isset($settings['enable_mp']) && ($settings['enable_mp'] == 1)){
                    $CustomRedirectionUrl = $this->_url->getUrl('retail/index');
                    $this->_session->setBeforeAuthUrl($CustomRedirectionUrl);
                    return $this;
                }
            }
//            if ((Mage::getSingleton('core/session')->getCustomerIsSeller() == 1) && Mage::getStoreConfig('vss/marketplace/active', Mage::app()->getStore()->getId())) {
//                $session = Mage::getSingleton('customer/session');
//                if (strpos(Mage::helper('core/http')->getHttpReferer(), 'checkout') === false){
//                    $session->setAfterAuthUrl(Mage::helper('marketplace')->getFrontUrl('index'));
//                }
//                else{
//                    $session->setAfterAuthUrl(Mage::helper('core/http')->getHttpReferer());
//                }
//
//                $session->setBeforeAuthUrl('');
//            }
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            // $this->mp_logHelper->createFileAndWriteLogData(
            //         \Hariyo\Marketplace\Helper\Log::INFOTYPEERROR, 'Observer RedirectToDashboard::execute()', $e->getMessage()
            // );
        }
    }
}
