<?php

namespace Knowband\Marketplace\Block\Earnings;

class Transactions extends \Magento\Framework\View\Element\Template {

    private $_seller_earning = 0;
    private $_total_paid = 0;
    private $_balance = 0;
    private $_default_sort = ['col'=> 'row_id', 'dir' => 'DESC'];
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Knowband\Marketplace\Model\Transactions $mpTransactionModel,
            \Knowband\Marketplace\Model\Earnings $mpEarningModel,
            \Knowband\Marketplace\Helper\Data $mpDataHelper
    ) {
        $this->_objectManager = $objectManager;
        $this->mp_dataHelper = $mpDataHelper;
        $this->mp_transactionModel = $mpTransactionModel;
        $this->mp_earningModel = $mpEarningModel;
        $this->_timezone = $timezone;
        parent::__construct($context);
        
        $seller_info = $this->mp_dataHelper->getSellerInfo();
		
        $reportsHelper = $this->_objectManager->get("\Knowband\Marketplace\Helper\Reports");
        //Calculate Total Earning
        $collection = $this->mp_earningModel->getCollection();
        $collection->getSelect()->join(['o' => $collection->getTable('sales_order')], 'o.entity_id = main_table.order_id');
        $collection->addFieldToFilter('o.status', ['nin' => $reportsHelper->getOrderNotInclude()]);
        $collection->addFieldToFilter('main_table.seller_id', ['eq' => $seller_info['entity_id']]);
        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS)
                ->columns(["SUM(main_table.seller_earning) as seller_earning"]);
        if($collection->getSize() > 0){
                $tmp = $collection->getData();
                $this->_seller_earning = $tmp[0]['seller_earning'];
        }
        
        unset($collection);

        //Calculate Total Amount Paid by Admin to seller
        $collection = $this->mp_transactionModel->getCollection();
        $collection->addFieldToFilter('seller_id', ['eq' => $seller_info['entity_id']]);
        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS)
                ->columns(["SUM(amount) as amount"]);
        if($collection->getSize() > 0){
                $tmp = $collection->getData();
                $this->_total_paid = $tmp[0]['amount'];
        }
        unset($collection);
        
        $this->_balance = $this->_seller_earning - $this->_total_paid;
    }
    
    
    public function getTotalEarning() {
        return $this->mp_dataHelper->formatCurrency($this->_seller_earning);
    }

    public function getTotalPaid() {
        return $this->mp_dataHelper->formatCurrency($this->_total_paid);
    }

    public function getBalance() {
        return $this->mp_dataHelper->formatCurrency($this->_balance);
    }

    public function getFieldId() {
        return 'vssmp_seller_transaction_history';
    }

    public function getFilters() {
        return [
            ['label' => __('From Date'), 'name' => 'from_date', 'className' => 'form-control has_datepicker', 'type' => 'date', 'values' => ''],
            ['label' => __('To Date'), 'name' => 'to_date', 'className' => 'form-control has_datepicker', 'type' => 'date', 'values' => ''],
            ['label' => __('Amount') . ' ' . __('From'), 'name' => 'amount_from', 'className' => 'form-control', 'type' => 'text', 'values' => ''],
            ['label' => __('Amount') . ' ' . __('To'), 'name' => 'amount_to', 'className' => 'form-control', 'type' => 'text', 'values' => ''],
            ['label' => __('Transaction Type'), 'name' => 'type', 'className' => 'form-control', 'type' => 'select', 'values' => $this->mp_transactionModel->getTransactionTypes()]
        ];
    }

    public function getColumns() {
        return [
            ['label' => __('Transaction Date'), 'name' => 'created_at', 'className' => '', 'targets' => 0, 'width' => '150'],
            ['label' => __('Transaction ID'), 'name' => 'transaction_id', 'className' => '', 'targets' => 1, 'width' => ''],
            ['label' => __('Comment'), 'name' => 'comment', 'className' => 'vssmp-prevent-txtflow', 'targets' => 2, 'width' => ''],
            ['label' => __('Transaction Type'), 'name' => 'type', 'className' => '', 'targets' => 3, 'width' => '120'],
            ['label' => __('Amount'), 'name' => 'amount', 'className' => 'vssmp-txt-ryt', 'targets' => 4, 'width' => '120']
        ];
    }

    public function getListUrl() {
        return $this->mp_dataHelper->getFrontUrl('earnings', 'getAjaxData', ['action' => 'transactionhistory']);
    }

    public function getList() {
        $seller_info = $this->mp_dataHelper->getSellerInfo();
        $post_data = $this->getRequest()->getParams();
        $col_order = $this->mp_dataHelper->getColOrder($post_data);

        $collection = $this->mp_transactionModel->getCollection();
        $collection->addFieldToFilter('seller_id', ['eq' => $seller_info['entity_id']]);

        $filters = $this->getFilters();
        foreach ($filters as $filter) {
            if (isset($post_data[$filter['name']]) && $post_data[$filter['name']] != '' && $filter['name'] != 'format') {
                if ($filter['type'] == 'select' || $filter['type'] == 'date') {
                    if ($filter['type'] == 'date' && $filter['name'] == 'from_date') {
                        $formatted_date = date(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT, strtotime($post_data[$filter['name']]));
                        $collection->getSelect()->where('DATE(main_table.created_at) >= "' . $formatted_date . '"');
                    } else if ($filter['type'] == 'date' && $filter['name'] == 'to_date') {
                        $formatted_date = date(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT, strtotime($post_data[$filter['name']]));
                        $collection->getSelect()->where('DATE(main_table.created_at) <= "' . $formatted_date . '"');
                    } else {
                        $collection->addFieldToFilter($filter['name'], ['eq' => $post_data[$filter['name']]]);
                    }
                } else if ($filter['type'] == 'text') {
                    if ($filter['name'] == 'amount_from' || $filter['name'] == 'amount_to') {
                        if ($filter['name'] == 'amount_from') {
                            $collection->getSelect()->where('amount >= ' . $post_data[$filter['name']]);
                        }
                        if ($filter['name'] == 'amount_to') {
                            $collection->getSelect()->where('amount <= ' . $post_data[$filter['name']]);
                        }
                    } else {
                        $collection->addFieldToFilter($filter['name'], ['like' => '%' . $post_data[$filter['name']] . '%']);
                    }
                }
            }
        }

        if (isset($col_order['col']) && $col_order['col'] != '') {
            $collection->getSelect()->order([$col_order['col'] . ' ' . $col_order['dir']]);
        } else {
            $collection->getSelect()->order($this->_default_sort['col'], $this->_default_sort['dir']);
        }

        $data = [];
        $countCollection = clone $collection;
        $data['count'] = count($countCollection->getData());

        $start = 0;
        $limit = $this->mp_dataHelper->getPageLength();
        if (isset($post_data['start']) && $post_data['start'] > 0) {
            $start = (int) $post_data['start'];
        }

        $collection->getSelect()->limit($limit, $start);

        $data['collection'] = [];
        if ($collection->getSize() > 0) {
            $type_singleton = $this->mp_transactionModel;
            foreach ($collection as $item) {
                $url = $this->mp_dataHelper->getFrontUrl('earnings', 'transactionDetail', ['id' => $item->getRowId()]);
                $read_more_link = '<a href="javascript:void(0)" onclick="getTransactionDetail(\'' . $url . '\')" title="click to view detail">Read More</a>';
                $data['collection'][] = [
                    $this->_timezone->formatDate($item->getCreatedAt()),
                    '<a href="javascript:void(0)" onclick="getTransactionDetail(\'' . $url . '\')" title="click to view detail">' . $item->getTransactionId() . '</a>',
                    $this->mp_dataHelper->clipLongText($item->getComment(), $read_more_link),
                    $type_singleton->getTypeLabel($item->getType()),
                    $this->mp_dataHelper->formatCurrency($item->getAmount())
                ];
            }
        }
        unset($collection);
        unset($countCollection);
        unset($type_singleton);
        return $data;
    }

}
