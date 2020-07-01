<?php

namespace Knowband\Marketplace\Controller\Adminhtml\Marketplace;

class EmailTemplatesAjax extends \Magento\Framework\App\Action\Action
{

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\LayoutFactory $viewLayoutFactory
    ) {
        parent::__construct($context);
        $this->_viewLayoutFactory = $viewLayoutFactory;
    }

    public function execute() {
        $block = $this->_viewLayoutFactory->create()->createBlock('Knowband\Marketplace\Block\Adminhtml\Grid\EmailTemplates');
        $this->getResponse()->appendBody($block->toHtml());
    }
}
