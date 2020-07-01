<?php

namespace Knowband\Marketplace\Block;

class TopLink extends \Magento\Framework\View\Element\Html\Links {

    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Customer\Model\Session $customerSession,
            \Knowband\Marketplace\Helper\Seller $mpSellerHelper, 
            \Knowband\Marketplace\Helper\Log $mpLogHelper
    ) {
        $this->mp_logHelper = $mpLogHelper;
        $this->mp_sellerHelper = $mpSellerHelper;
        $this->_customerSession = $customerSession;
        parent::__construct($context);
    }
    
    /**
     * Render block HTML.
     *
     * @return string
     */
    protected function _toHtml() {
        try {
            $session = $this->_customerSession;
            if ($session->isLoggedIn() && $this->mp_sellerHelper->isSeller($session->getCustomer()->getId())) {
                if (false != $this->getTemplate()) {
                    return parent::_toHtml();
                }
                return '<li><a href=' . $this->getLinkAttributes() . ' title="' . $this->getTitle() . '">' . $this->escapeHtml($this->getLabel()) . '</a></li>';
            }
        } catch (\Exception $ex) {
            $this->mp_logHelper->createFileAndWriteLogData(
                \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Block TopLink::_toHtml()', $ex->getMessage()
            );
        }
        return parent::_toHtml();
    }

    public function getLinkAttributes(){
        return $this->getUrl("marketplace/index/index");
    }
    
    public function getTitle(){
        return  __('Seller Account');
    }

}
