<?php

namespace Knowband\Marketplace\Controller\Adminhtml\Marketplace;

class Cron extends \Magento\Backend\App\Action
{
    public $resultPageFactory = false;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();        
        $resultPage->setActiveMenu('Knowband_Marketplace::cron');
        $resultPage->getConfig()->getTitle()->prepend(__('CRON Instructions'));
        $resultPage->addBreadcrumb(__('Knowband'), __('Knowband'));
        $resultPage->addBreadcrumb(__('Marketplace'), __('Marketplace'));
        return $resultPage;
        
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Knowband_Marketplace::cron');
    }
}
