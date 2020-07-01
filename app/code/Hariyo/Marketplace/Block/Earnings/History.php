<?php

namespace Hariyo\Marketplace\Block\Earnings;

class History extends \Magento\Framework\View\Element\Template {

    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
            \Hariyo\Marketplace\Model\Earnings $mpEarningsModel,
            \Hariyo\Marketplace\Helper\Data $mpDataHelper
    ) {
        $this->_objectManager = $objectManager;
        $this->mp_earningsModel = $mpEarningsModel;
        $this->mp_dataHelper = $mpDataHelper;
        $this->_timezone = $timezone;
        parent::__construct($context);
    }
    
    public function getFieldId() {
        return 'vssmp_seller_earning_history';
    }

    public function getFilters() {
        $reportsHelper = $this->_objectManager->get("\Hariyo\Marketplace\Helper\Reports");
        return [
            ['label' => __('From Date'), 'name' => 'from_date', 'className' => 'form-control has_datepicker', 'type' => 'date', 'values' => ''],
            ['label' => __('To Date'), 'name' => 'to_date', 'className' => 'form-control has_datepicker', 'type' => 'date', 'values' => ''],
            ['label' => __('Report Format'), 'name' => 'format', 'className' => 'form-control', 'type' => 'select', 'values' => $reportsHelper->getAllReportFormat()]
        ];
    }

    public function getColumns() {
        return [
            ['label' => __('Date'), 'name' => 'main_table.created_at', 'className' => '', 'targets' => 0, 'width' => '150'],
            ['label' => __('Total Orders'), 'name' => 'total_order', 'className' => 'vssmp-txt-ryt', 'targets' => 1, 'width' => ''],
            ['label' => __('Total Products Sold'), 'name' => 'product_count', 'className' => 'vssmp-txt-ryt', 'targets' => 2, 'width' => ''],
            ['label' => __('Order Total'), 'name' => 'total_earning', 'className' => 'vssmp-txt-ryt', 'targets' => 3, 'width' => ''],
            ['label' => __('Your Earnings'), 'name' => 'seller_earning', 'className' => 'vssmp-txt-ryt', 'targets' => 4, 'width' => '']
        ];
    }

    public function getListUrl() {
        return $this->mp_dataHelper->getFrontUrl('earnings', 'getAjaxData', ['action' => 'history']);
    }

    public function getList() {
        $seller_info = $this->mp_dataHelper->getSellerInfo();
        $reportsHelper = $this->_objectManager->get("\Hariyo\Marketplace\Helper\Reports");
        $post_data = $this->getRequest()->getParams();
        $col_order = $this->mp_dataHelper->getColOrder($post_data);
        $report_format = (isset($post_data['format']) && array_key_exists($post_data['format'], $reportsHelper->getAllReportFormat()) ? $post_data['format'] : \Hariyo\Marketplace\Helper\Reports::REPORT_FORMAT_DAILY);

        $collection = $this->mp_earningsModel->getCollection();
        
        $collection->getSelect()->join(['o' => $collection->getTable('sales_order')], 'o.entity_id = main_table.order_id');

        $collection->addFieldToFilter('o.status', ['nin' => $reportsHelper->getOrderNotInclude()]);

        $collection->getSelect()
                ->reset(\Zend_Db_Select::COLUMNS);

        $collection = $this->reportType($collection, $report_format);

        $collection->addFieldToFilter('main_table.seller_id', ['eq' => $seller_info['entity_id']]);

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
                    $collection->addFieldToFilter($filter['name'], ['like' => '%' . $post_data[$filter['name']] . '%']);
                }
            }
        }

        if (isset($col_order['col']) && $col_order['col'] != '') {
            $collection->getSelect()->order([$col_order['col'] . ' ' . $col_order['dir']]);
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

        if ($data['count'] > 0) {
            $rows = $collection->getData();
            foreach ($rows as $row) {
                $data['collection'][] = [
                    $this->IntervalRenderer($row, $report_format),
                    (int) $row['total_order'],
                    (int) $row['product_count'],
                    $this->mp_dataHelper->formatCurrency($row['total_earning']),
                    $this->mp_dataHelper->formatCurrency($row['seller_earning'])
                ];
            }
        } else {
            $data['collection'] = [];
        }
        
        unset($collection);
        unset($countCollection);
        return $data;
    }

    public function reportType(&$collection, $type) {
        $collection->addExpressionFieldToSelect("total_order", 'COUNT({{main_table.order_id}})', ["main_table.order_id" => "main_table.order_id"]);
        $collection->addExpressionFieldToSelect("product_count", 'SUM({{main_table.product_count}})', ["main_table.product_count" => "main_table.product_count"]);
        $collection->addExpressionFieldToSelect("total_earning", 'SUM({{main_table.total_earning}})', ["main_table.total_earning" => "main_table.total_earning"]);
        $collection->addExpressionFieldToSelect("seller_earning", 'SUM({{main_table.seller_earning}})', ["main_table.seller_earning" => "main_table.seller_earning"]);
        switch ($type) {
            case \Hariyo\Marketplace\Helper\Reports::REPORT_FORMAT_DAILY: {
                    $collection->addExpressionFieldToSelect("from_date", '{{main_table.created_at}}', ["main_table.created_at" => "main_table.created_at"]);
                    $collection->getSelect()->group('DATE(main_table.created_at)');
                    break;
                }
            //Weekly report will give data from Sunday to Saturday
            case \Hariyo\Marketplace\Helper\Reports::REPORT_FORMAT_WEEKLY: {
                    $collection->addExpressionFieldToSelect("from_date", 'DATE_FORMAT(DATE_ADD({{main_table.created_at}}, INTERVAL(1-DAYOFWEEK({{main_table.created_at}})) DAY),"%Y-%m-%e")', ["main_table.created_at" => "main_table.created_at"]);
                    $collection->addExpressionFieldToSelect("to_date", 'DATE_FORMAT(DATE_ADD({{main_table.created_at}}, INTERVAL(7-DAYOFWEEK({{main_table.created_at}})) DAY),"%Y-%m-%e")', ["main_table.created_at" => "main_table.created_at"]);
                    $collection->getSelect()->group('YEARWEEK(main_table.created_at)');
                    break;
                }
            case \Hariyo\Marketplace\Helper\Reports::REPORT_FORMAT_MONTHLY: {
                    $collection->addExpressionFieldToSelect("from_date", 'DATE_FORMAT(main_table.created_at,"%b-%Y")', ["main_table.created_at" => "main_table.created_at"]);
                    $collection->getSelect()->group('from_date');
                    break;
                }
            case \Hariyo\Marketplace\Helper\Reports::REPORT_FORMAT_YEARLY: {
                    $collection->addExpressionFieldToSelect("from_date", 'DATE_FORMAT(main_table.created_at,"%Y")', ["main_table.created_at" => "main_table.created_at"]);
                    $collection->getSelect()->group('from_date');
                    break;
                }
        }
        return $collection;
    }

    private function IntervalRenderer($data, $type) {
        $html = '';
        switch ($type) {
            case \Hariyo\Marketplace\Helper\Reports::REPORT_FORMAT_DAILY: {
                    $html .= $this->_timezone->formatDate($data['from_date'], \IntlDateFormatter::SHORT);
                    break;
                }
            //Weekly report will give data from Sunday to Saturday
            case \Hariyo\Marketplace\Helper\Reports::REPORT_FORMAT_WEEKLY: {
                    $html .= $this->_timezone->formatDate($data['from_date'], \IntlDateFormatter::SHORT) . ' to ' . $this->_timezone->formatDate($data['to_date'], \IntlDateFormatter::SHORT);
                    break;
                }
            case \Hariyo\Marketplace\Helper\Reports::REPORT_FORMAT_MONTHLY: {
                    $html .= $data['from_date'];
                    break;
                }
            case \Hariyo\Marketplace\Helper\Reports::REPORT_FORMAT_YEARLY: {
                    $html .= $data['from_date'];
                    break;
                }
        }
        return $html;
    }

}
