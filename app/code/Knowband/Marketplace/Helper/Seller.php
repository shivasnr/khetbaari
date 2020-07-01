<?php

/**
 * Knowband_Marketplace
 *
 * @category    Knowband
 * @package     Knowband_Marketplace
 * @author      Knowband Team <support@knowband.com.com>
 * @copyright   Knowband (http://wwww.knowband.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Knowband\Marketplace\Helper;
class Seller extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $mp_storeManager;
    protected $mp_scopeConfig;
    protected $mp_request;
    protected $rulesFactory;
    protected $mp_objectManager;
    
    CONST SELLER_DEFAULT_TITLE = 'Not Available';
    CONST SELLER_REGISTER_ALLOWED = 3;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configResource,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Knowband\Marketplace\Model\Settings $mpSettingModel,
        \Knowband\Marketplace\Model\Seller $mpSellerModel,
        \Knowband\Marketplace\Helper\Setting $mpSettingHelper,
        \Knowband\Marketplace\Helper\Log $mpLogger
    )
    {
        $this->mp_storeManager = $storeManager;
        $this->moduleManager = $context->getModuleManager();
        $this->mp_scopeConfig = $context->getScopeConfig();
        $this->mp_request = $context->getRequest();
        $this->mp_resource = $configResource;
        $this->mp_objectManager = $objectManager;
        $this->date = $date;
        $this->_registry = $registry;
        $this->mp_settingModel = $mpSettingModel;
        $this->mp_sellerModel = $mpSellerModel;
        $this->mp_logHelper = $mpLogger;
        $this->mp_settingHelper = $mpSettingHelper;
        parent::__construct($context);
    }
    
    public function getSellerInfo() {
        return $this->_registry->registry('vssmp_seller_info');
    }
    
    public function getFrontUrl($controller, $action = null, $params = []) {
        $url = 'marketplace/' . $controller;
        if (!empty($action)) {
            $url .= '/' . $action;
        }
        return $this->_getUrl($url, $params);
    }
    
    public function isSeller($seller_id = 0) {
        if ($seller_id > 0) {
            $seller_info = ['entity_id' => $seller_id];
        } else {
            $seller_info = $this->getSellerInfo();
        }
        $exist = $this->mp_sellerModel->getCollection();
        $exist->addFieldToFilter('seller_id', ['eq' => $seller_info['entity_id']]);
        $count = $exist->getSize();
        unset($exist);
        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    public function isApprovedSeller($seller_id = 0) {
        if ($seller_id > 0) {
            $seller_info = array('entity_id' => $seller_id);
        } else {
            $seller_info = $this->getSellerInfo();
        }
        $exist = $this->mp_sellerModel->getCollection();
        $exist->addFieldToFilter('seller_id', ['eq' => $seller_info['entity_id']]);
        $exist->addFieldToFilter('seller_approved', ['eq' => \Knowband\Marketplace\Helper\GridAction::APPROVED]);
        if ($exist->getSize() > 0 ) {
            return true;
        } else {
            return false;
        }
    }

    public function isDisapprovedSeller($seller_id = 0) {
        if ($seller_id > 0) {
            $seller_info = array('entity_id' => $seller_id);
        } else {
            $seller_info = $this->getSellerInfo();
        }
        $exist = $this->mp_sellerModel->getCollection();
        $exist->addFieldToFilter('seller_id', ['eq' => $seller_info['entity_id']]);
        $exist->addFieldToFilter('seller_approved', ['eq' => \Knowband\Marketplace\Helper\GridAction::DISAPPROVED]);
        if ($exist->getSize() > 0 ) {
            return true;
        } else {
            return false;
        }
    }

    public function isEnabledSeller($seller_id = 0) {
        if ($seller_id > 0) {
            $seller_info = ['entity_id' => $seller_id];
        } else {
            $seller_info = $this->getSellerInfo();
        }

        $exist = $this->mp_sellerModel->getCollection();
        $exist->addFieldToFilter('seller_id', ['eq' => $seller_info['entity_id']]);
        $exist->addFieldToFilter('seller_enabled', ['eq' => \Knowband\Marketplace\Helper\GridAction::ENABLED]);
        if ($exist->getSize() > 0 ) {
            return true;
        } else {
            return false;
        }
    }

    public function isSellerUrlExist($key_url, $seller_id = 0) {
        if ($seller_id > 0) {
            $coll = $this->mp_sellerModel->getCollection()
                    ->addFieldToFilter('seller_id', ['neq' => $seller_id])
                    ->addFieldToFilter('page_url_key', ['eq' => $key_url]);
            
            if ($coll->getSize() > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            $coll = $this->mp_sellerModel->load($key_url, 'page_url_key');
            $coll_data = $coll->getData();
            $coll->unsetData();
            if (is_array($coll_data) && isset($coll_data['page_url_key']) && !empty($coll_data['page_url_key'])) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function generate_seller_page_url($input, $seller_id = 0) {
        $output = strtolower(trim($input));
        $output = str_replace(" ", "-", $output);
        $output = str_replace("--", "-", $output);
        $output = str_replace("/", "", $output);
        $output = str_replace("\\", "", $output);
        $output = str_replace("'", "", $output);
        $output = str_replace(",", "", $output);
        $output = str_replace(";", "", $output);
        $output = str_replace(":", "", $output);
        $output = str_replace(".", "-", $output);
        $output = str_replace("?", "", $output);
        $output = str_replace("=", "-", $output);
        $output = str_replace("+", "", $output);
        $output = str_replace("$", "", $output);
        $output = str_replace("&", "", $output);
        $output = str_replace("!", "", $output);
        $output = str_replace(">>", "-", $output);
        $output = str_replace(">", "-", $output);
        $output = str_replace("<<", "-", $output);
        $output = str_replace("<", "-", $output);
        $output = str_replace("*", "", $output);
        $output = str_replace(")", "", $output);
        $output = str_replace("(", "", $output);
        $output = str_replace("[", "", $output);
        $output = str_replace("]", "", $output);
        $output = str_replace("^", "", $output);
        $output = str_replace("%", "", $output);
        $output = str_replace("?", "-", $output);
        $output = str_replace("|", "", $output);
        $output = str_replace("#", "", $output);
        $output = str_replace("@", "", $output);
        $output = str_replace("`", "", $output);
        $output = str_replace("?", "", $output);
        $output = str_replace("?", "", $output);
        $output = str_replace("--", "-", $output);
        $output = str_replace("_", "-", $output);
        $output = str_replace("__", "-", $output);
        $output = str_replace("\"", "", $output);

        $collection = $this->mp_sellerModel->getCollection();
        $collection->getSelect()->where('page_url_key = "' . $output . '" AND seller_id != ' . (int) $seller_id);
        $count = $collection->getSize();
        unset($collection);
        if ($count > 0) {
            return $output . $count;
        } else {
            return $output;
        }
    }

}
