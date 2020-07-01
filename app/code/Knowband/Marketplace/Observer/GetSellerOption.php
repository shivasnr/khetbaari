<?php

namespace Knowband\Marketplace\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class GetSellerOption implements ObserverInterface {

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
            \Magento\Framework\Session\SessionManagerInterface $sessionManager, 
            \Psr\Log\LoggerInterface $logger, 
            \Magento\Framework\App\Request\Http $request, 
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Knowband\Marketplace\Helper\Log $mpLogHelper
    ) {
        $this->_sessionManager = $sessionManager;
        $this->_logger = $logger;
        $this->mp_request = $request;
        $this->mp_logHelper = $mpLogHelper;
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
            $post_data = $this->mp_request->getPost();
            if (isset($post_data['is_seller']) && $post_data['is_seller']) {
                 $this->_sessionManager->setRegisterAsSeller(1);
            }else{
                $this->_sessionManager->setRegisterAsSeller(0);
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            $this->mp_logHelper->createFileAndWriteLogData(
                    \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Observer GetSellerOption::execute()', $e->getMessage()
            );
        }
    }

}
