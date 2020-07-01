<?php

/**
 * Hariyo_Marketplace
 *
 * @category    Hariyo
 * @package     Hariyo_Marketplace
 * @author      Hariyo Team <support@Hariyo.com.com>
 * @copyright   Hariyo (http://wwww.Hariyo.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Hariyo\Marketplace\Helper;
class GridAction extends \Magento\Framework\App\Helper\AbstractHelper
{
    CONST WAITING_APPROVAL = 0;
    CONST APPROVED = 1;
    CONST DISAPPROVED = 2;	

    //Statuses
    CONST DISABLED = 0;
    CONST ENABLED = 1;

    CONST VIEW = -1;
    CONST DELETE = -2;

    CONST APPROVE_ACTION = 'approve';
    CONST DISAPPROVE_ACTION = 'disapprove';

    CONST ACTION_PRODUCT_APPROVAL = 1;
    CONST ACTION_SELLER_APPROVAL = 2;
    CONST ACTION_SELLER_ENABLE = 3;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Eav\Model\Entity\Type $entityType,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Backend\Model\Auth\Session $adminSession,
        \Magento\Eav\Model\Entity\Attribute $entityAttribute,
        \Hariyo\Marketplace\Model\Product $productToSeller,
        \Hariyo\Marketplace\Model\Reason $mpReasonModel,
        \Hariyo\Marketplace\Model\Settings $mpSettingsModel,
        \Hariyo\Marketplace\Helper\Email $mpEmailHelper,
        \Hariyo\Marketplace\Helper\Setting $mpSettingHelper,
        \Psr\Log\LoggerInterface $mpLogHelper
    )
    {
        $this->_storeManager = $storeManager;
        $this->_adminSession = $adminSession;
        $this->_entityType = $entityType;
        $this->_request = $request;
        $this->date = $date;
        $this->_entityAttribute = $entityAttribute;
        $this->mp_productToSellerModel = $productToSeller;
        $this->mp_reasonModel = $mpReasonModel;
        $this->mp_settingModel = $mpSettingsModel;
        $this->mp_logHelper = $mpLogHelper;
        $this->mp_emailHelper = $mpEmailHelper;
        $this->mp_settingHelper = $mpSettingHelper;
        $this->logger = $context->getLogger();
        parent::__construct($context);
    }
    
    public function getDate() {
        return $this->date->date();
    }
    
    public function getApprovalOptionArray() {
        $status_arr =  [
            self::WAITING_APPROVAL => __('Waiting for Approval'),
            self::APPROVED => __('Approved'),
            self::DISAPPROVED => __('Disapproved')
        ];
        return $status_arr;
    }

    public function getLabel($code) {
        switch ($code) {
            case self::WAITING_APPROVAL: {
                    return __('Waiting for Approval');
                }
            case self::APPROVED: {
                    return __('Approved');
                }
            case self::DISAPPROVED: {
                    return __('Disapproved');
                }
        }
        return '';
    }

    public function getStatusOptionArray() {
        return [
            self::ENABLED => __('Enabled'),
            self::DISABLED => __('Disabled')
        ];
    }
    
    public function getScope(){
        if ($this->_request->getParam('store')) {
            $scope_id = $this->_storeManager->getStore($this->_request->getParam('store'))->getId();
            $scope = "stores";
        } elseif ($this->_request->getParam('website')) {
            $scope_id = $this->_storeManager->getWebsite($this->_request->getParam('website'))->getId();
            $scope = "websites";
        } elseif ($this->_request->getParam('group')) {
            $scope_id = $this->_storeManager->getGroup($this->_request->getParam('group'))->getWebsite()->getId();
            $scope = "groups";
        } else {
            $scope = "default";
            $scope_id = 0;
        }
        
        return ['scope' => $scope,'scope_id' => $scope_id];
    }
    
    public function setAdminGridFilter(&$collection, $column, $col_name, $value = '') {
        if ($column->getFilter()) {
            if ($type = $column->getType()) {
                if ($type == 'number' || $type == 'currency' || $type == 'price') {
                    $query = '';
                    if (isset($value['from']) && isset($value['to'])) {
                        $query .= $col_name . ' >= ' . $value['from'] . ' AND ' . $col_name . ' <= ' . $value['to'];
                    } else if (isset($value['from'])) {
                        $query .= $col_name . ' >= ' . $value['from'];
                    } else if (isset($value['to'])) {
                        $query .= $col_name . ' <= ' . $value['to'];
                    }
                    if ($query != '') {
                        $collection->getSelect()->where($query);
                    }
                } else if ($type == 'datetime' || $type == 'date') {
                    $query = '';
                    if (isset($value['from']) && isset($value['to'])) {
                        $query .= $col_name . ' >= "' . date(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT, strtotime($value['from'])) . '"' . ' AND ' . $col_name . ' <= "' . date(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT, strtotime($value['to'])) . '"';
                    } else if (isset($value['from'])) {
                        $query .= $col_name . ' >= "' . date(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT, strtotime($value['from'])) . '"';
                    } else if (isset($value['to'])) {
                        $query .= $col_name . ' <= "' . date(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT, strtotime($value['to'])) . '"';
                    }
                    if ($query != '') {
                        $collection->getSelect()->where($query);
                    }
                } else {
                    $collection->getSelect()->where($col_name . ' LIKE "%' . $value . '%"');
                }
            } else {
                $collection->getSelect()->where($col_name . ' LIKE "%' . $value . '%"');
            }
        }

        return $collection;
    }

	
    public function getFirstNameAttribute() {
        $entity = $this->_entityType->load('customer', 'entity_type_code');
        $firstNameAttr = $this->_entityAttribute->loadByCode($entity->getId(), 'firstname');
        return $firstNameAttr;
        
    }

    public function getLastNameAttribute() {
        $entity = $this->_entityType->load('customer', 'entity_type_code');
        return $this->_entityAttribute->loadByCode($entity->getId(), 'lastname');
    }
    
    

    public function actionOnProduct($action, $product_id, $rsn = '') {
        $rsn_code = '';
        $dt = '0000-00-00 00:00:00';
        $make_entry_in_rsn = true;
        if ($action == self::APPROVED) {
            $rsn_code = 'MPRSN002';
            $make_entry_in_rsn = false;
        } else if ($action == self::DISAPPROVED) {
            $rsn_code = 'MPRSN003';
        }
        if ($product_id) {
            $seller_details = $this->mp_productToSellerModel->getCollection();
            $seller_details->addFieldToFilter('product_id', ['eq' => $product_id]);
            $seller_data = $seller_details->getData();
            unset($seller_details);
        }

        try {
            $insert_rsn = $this->mp_reasonModel;
            if ($make_entry_in_rsn) {
                $insert_rsn->addData([
                    'reason_type' => $rsn_code,
                    'seller_id' => $seller_data[0]["seller_id"],
                    'seller_product_id' => $product_id,
                    'reason_text' => $rsn,
                    'updated_at' => $this->getDate()
                ]);
               if ($this->_adminSession->getUser()->getUserId()) {
                    $insert_rsn->setAdminId($this->_adminSession->getUser()->getUserId());
                }
                $insert_rsn->save();
                $insert_rsn->unsetData();
            }

            //Change approved status of product
            $change_action = $this->mp_productToSellerModel->getCollection();
            $change_action->addFieldToFilter('seller_id', ['eq' => $seller_data[0]["seller_id"]]);
            $change_action->addFieldToFilter('product_id', ['eq' => $product_id]);
            $data = $change_action->getData();
            unset($change_action);
            if (!empty($data)) {
                $update_col = $this->mp_productToSellerModel->load($data[0]['seller_product_id']);
                $status_update['approved'] = $action;
                $update_col->setApproved($action);
                if ($action == self::APPROVED) {
                    $update_col->setApprovedDate($this->getDate());
                } else if ($action == self::DISAPPROVED) {
                    $update_col->setDisapprovedDate($this->getDate());
                }
                $update_col->save();
                $update_col->unsetData();
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
            // $this->mp_logHelper->createFileAndWriteLogData(
            //         \Hariyo\Marketplace\Helper\Log::INFOTYPEERROR, 'Helper GridAction::actionOnProduct()', $e->getMessage()
            // );
            return false;
        }
        return true;
    }
    
    public function getCategoryNameAttribute() {
        $entity = $this->_entityType->load('catalog_category', 'entity_type_code');
        return $this->_entityAttribute->loadByCode($entity->getId(), 'name');
    }

}
