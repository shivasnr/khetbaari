<?php

namespace Knowband\Marketplace\Block\Adminhtml;

class FormBlock extends \Magento\Backend\Block\Template {

    public function __construct(
            \Magento\Backend\Block\Template\Context $context,
            \Magento\Framework\Registry $registry,
            \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customColFactory,
            \Knowband\Marketplace\Model\Transactions $mpTransacionModel,
            \Knowband\Marketplace\Helper\GridAction $mpGridactionHelper
    ) {
        $this->_coreRegistry = $registry;
        $this->_customerColFactory = $customColFactory;
        $this->mp_gridActionHelper = $mpGridactionHelper;
        $this->mp_transactionModel = $mpTransacionModel;
        parent::__construct($context);
    }

    protected function _prepareLayout() {
        parent::_prepareLayout();
        return $this;
    }
    
    public function getFormActionUrl(){
        $action_submit_url = $this->_coreRegistry->registry("reason_submit_action");
        $this->_coreRegistry->unregister("reason_submit_action");
        return $action_submit_url;
    }
    
    public function getSellerList() {
        $scope = $this->mp_gridActionHelper->getScope();

        $collection = $this->_customerColFactory->create()
                ->addNameToSelect()
                ->addAttributeToSelect('email')
                ->addAttributeToSelect('created_at');
        $collection->getSelect()->join(['s2c' => $collection->getTable('vss_mp_seller_entity')], 'e.entity_id = s2c.seller_id');
        if ($scope['scope'] == 'websites') {
            $collection->addFieldToFilter('s2c.website_id', ['eq' => $scope['scope_id']]);
        } else if ($scope['scope'] == 'stores') {
            $collection->addFieldToFilter('s2c.store_id', ['eq' => $scope['scope_id']]);
        }

        $collection->getSelect()
                ->reset(\Zend_Db_Select::COLUMNS)
                ->columns(array(
//                    "CONCAT(IF(at_prefix.value IS NOT NULL AND at_prefix.value != '', CONCAT(LTRIM(RTRIM(at_prefix.value)), ' '), ''), LTRIM(RTRIM(at_firstname.value)), ' ', IF(at_middlename.value IS NOT NULL AND at_middlename.value != '', CONCAT(LTRIM(RTRIM(at_middlename.value)), ' '), ''), LTRIM(RTRIM(at_lastname.value)), IF(at_suffix.value IS NOT NULL AND at_suffix.value != '', CONCAT(' ', LTRIM(RTRIM(at_suffix.value))), '')) AS name",
                    "e.email as email",
                    "s2c.seller_id"));
        $collection->getSelect()
                ->columns(new \Zend_Db_Expr("CONCAT(`e`.`firstname`, ' ',`e`.`lastname`) AS name"));
        $data = [];
        if ($collection->getSize() > 0) {
            $tmp = $collection->getData();
            foreach ($tmp as $row) {
                $data[] = [
                    'label' => $row['name'] . '(' . $row['email'] . ')',
                    'value' => $row['seller_id']
                ];
            }
        }
        unset($collection);
        return $data;
    }
    
    public function getTransactionTypes(){
        return $this->mp_transactionModel->getTransactionTypes();
    }
    
    public function getDefaultTransactionType(){
        return $this->mp_transactionModel->getDefaultType();
    }

}


