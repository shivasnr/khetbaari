<?php

namespace Hariyo\Marketplace\Block;

class Page extends \Magento\Framework\View\Element\Template {

    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Framework\Registry $registry,
            \Hariyo\Marketplace\Helper\Setting $mpSettingHelper, 
            \Psr\Log\LoggerInterface $mpLogHelper, 
            \Hariyo\Marketplace\Model\Seller $mpSellerModel
    ) {
        $this->mp_settingsHelper = $mpSettingHelper;
        $this->mp_logHelper = $mpLogHelper;
        $this->mp_sellerModel = $mpSellerModel;
        $this->_coreRegistry = $registry;
        parent::__construct($context);
    }

    public function getSellerMenuHtml() {
        $blockObj= $this->getLayout()->createBlock('Hariyo\Marketplace\Block\Sellermenu');
        return $blockObj->toHtml();
    }

    public function printAccountWarning() {
        try{
            $seller = $this->_coreRegistry->registry('vssmp_seller_info');
            $sellerModel = $this->mp_sellerModel->load($seller['entity_id'], 'customer_id');
            $sellerApproved = $sellerModel->getSellerApproved();
            $sellerModel->unsetData();
            if ($sellerApproved == \Hariyo\Marketplace\Helper\GridAction::APPROVED) {
                return ['flag' => false, 'msg' => ''];
            } else if($sellerApproved == \Hariyo\Marketplace\Helper\GridAction::WAITING_APPROVAL){
                return ['flag' => true, 'msg' => __('Your seller account is currently under process and not yet approved. You only have a limited access to the seller account currently.')];
            } else if($sellerApproved == \Hariyo\Marketplace\Helper\GridAction::DISAPPROVED){
                $msg = __('Your seller account has been disapproved. You only have a limited access to the seller account currently. To send request again ');
                $msg .= '<a href="'.$this->getUrl("marketplace/sellers/sellerRequest") . '">'.__('Click Here')."</a>";
                return ['flag' => true, 'msg' => $msg];
            }
            return ['flag' => false, 'msg' => ''];
        }catch (\Exception $e){
            return ['flag' => true, 'msg' => $e->getMessage()];
        }
    }

    public function getReasonPopUpHtml() {
        $content = $this->getLayout()->createBlock('Hariyo\Marketplace\Block\Reasonpop');
        return $content->toHtml();
    }
    
    public function isActive($key) {
        $request = $this->getRequest();
        $controllerName = $request->getControllerName();
        $moduleName = $request->getModuleName();
        $actionName = $request->getActionName();
        $activeLink = $moduleName . '/' . $controllerName . '/' . $actionName;
        if (trim($key) == trim($activeLink)) {
            return true;
        }
        return false;
    }

}
