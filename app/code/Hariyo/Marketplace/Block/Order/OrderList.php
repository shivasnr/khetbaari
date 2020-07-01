<?php

namespace Hariyo\Marketplace\Block\Order;

class OrderList extends \Magento\Framework\View\Element\Template {
    
    
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Magento\Framework\Registry $registry,
            \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
            \Magento\Sales\Model\Order\ItemFactory $orderItemFactory,
            \Magento\Sales\Model\Order\ConfigFactory $configFactory,
            \Hariyo\Marketplace\Helper\Data $mpDataHelper
    ) {
        $this->_coreRegistry = $registry;
        $this->_orderItemFactory = $orderItemFactory;
        $this->_objectManager = $objectManager;
        $this->_configFactory = $configFactory;
        $this->_timezone = $timezone;
        $this->mp_dataHelper = $mpDataHelper;
        parent::__construct($context);
        $this->setTemplate('order/order_list.phtml');
    }
    
    public function getFieldId() {
        return 'vssmp_seller_orders';
    }

    public function getFilters() {
        $orderConfig = $this->_configFactory->create();
        return [
            ['label' => __('Order No.'), 'name' => 'increment_id', 'className' => 'form-control', 'type' => 'text', 'values' => ''],
            ['label' => __('From Date'), 'name' => 'from_date', 'className' => 'form-control has_datepicker', 'type' => 'date', 'values' => ''],
            ['label' => __('To Date'), 'name' => 'to_date', 'className' => 'form-control has_datepicker', 'type' => 'date', 'values' => ''],
            ['label' => __('Customer'), 'name' => 'full_name', 'className' => 'form-control', 'type' => 'text', 'values' => ''],
            ['label' => __('Status'), 'name' => 'status', 'className' => 'form-control', 'type' => 'select', 'values' => $orderConfig->getStatuses()]
        ];
    }

    public function getColumns() {
        return [
            ['label' => __('Order No.'), 'name' => 'increment_id', 'className' => 'vssmp-txt-ryt', 'targets' => 0, 'width' => 80],
            ['label' => __('Order Date'), 'name' => 'order_created_at', 'className' => '', 'targets' => 1, 'width' => 100,],
            ['label' => __('Customer'), 'name' => 'full_name', 'className' => '', 'targets' => 2, 'width' => 150],
            ['label' => __('Quantity'), 'name' => 'custom_qty', 'className' => 'vssmp-txt-ryt', 'targets' => 3, 'width' => ''],
            ['label' => __('Status'), 'name' => 'order.status', 'className' => '', 'targets' => 4, 'width' => ''],
            ['label' => __('Total'), 'name' => 'custom_row_total', 'className' => 'vssmp-txt-ryt', 'targets' => 5, 'width' => ''],
            ['label' => '', 'name' => '', 'className' => '', 'targets' => 6, 'width' => 150]
        ];
    }

    public function getSellerInfo() {
        return $this->_coreRegistry->registry("vssmp_seller_info");
    }

    public function getListUrl() {
        return $this->getFrontUrl('order', 'orderList');
    }
    
    public function getFrontUrl($controller, $action = '', $params = []){
        return $this->mp_dataHelper->getFrontUrl($controller, $action, $params);
    }

    public function getSellerOrderList() {
        $seller_info = $this->getSellerInfo();

        $post_data = $this->getRequest()->getParams();
        $col_order = $this->mp_dataHelper->getColOrder($post_data);

        $itemsCollection = $this->_orderItemFactory->create()->getCollection();
        $itemsCollection->getSelect()->join(['order' => $itemsCollection->getTable('sales_order')], "main_table.order_id=order.entity_id");

        $itemsCollection->getSelect()
                ->join(
                        ['seller_product' => $itemsCollection->getTable("vss_mp_product_to_seller")], 
                        "(main_table.product_id = seller_product.product_id and seller_product.seller_id=" . $seller_info['entity_id'] . ")", 
                        []
        );

        $itemsCollection->addExpressionFieldToSelect("custom_qty", 'SUM({{main_table.qty_ordered}} - {{main_table.qty_canceled}})', ["main_table.qty_ordered" => "main_table.qty_ordered", "main_table.qty_canceled" => "main_table.qty_canceled"]);
        $itemsCollection->addExpressionFieldToSelect("row_total_incl_tax", 'SUM({{main_table.row_total_incl_tax}})', ["main_table.row_total_incl_tax" => "main_table.row_total_incl_tax"]);
        $itemsCollection->addExpressionFieldToSelect("discount_amount", 'SUM({{main_table.discount_amount}})', ["main_table.discount_amount" => "main_table.discount_amount"]);

        $itemsCollection->getSelect()->columns(new \Zend_Db_Expr("CONCAT_WS(' ', `order`.`customer_firstname`, `order`.`customer_middlename`, `order`.`customer_lastname`) AS full_name"));

        $itemsCollection->addAttributeToFilter('main_table.parent_item_id', ['null' => true]);

        $itemsCollection->getSelect()->group("main_table.order_id"); // make sure we group

        $filters = $this->getFilters();
        foreach ($filters as $filter) {
            if (isset($post_data[$filter['name']]) && $post_data[$filter['name']] != '') {
                if ($filter['type'] == 'select' || $filter['type'] == 'date') {
                    if ($filter['type'] == 'date' && $filter['name'] == 'from_date') {
                        $formatted_date = date('Y-m-d', strtotime($post_data[$filter['name']]));
                        $itemsCollection->getSelect()->where('DATE(order.created_at) >= "' . $formatted_date . '"');
                    } else if ($filter['type'] == 'date' && $filter['name'] == 'to_date') {
                        $formatted_date = date('Y-m-d', strtotime($post_data[$filter['name']]));
                        $itemsCollection->getSelect()->where('DATE(order.created_at) <= "' . $formatted_date . '"');
                    } else {
                        $itemsCollection->addFieldToFilter($filter['name'], ['eq' => $post_data[$filter['name']]]);
                    }
                } else if ($filter['type'] == 'text') {
                    $itemsCollection->getSelect()->where("CONCAT_WS(' ', `order`.`customer_firstname`, `order`.`customer_middlename`, `order`.`customer_lastname`, `order`.`increment_id`) LIKE '%" . $post_data[$filter['name']] . "%'");
                }
            }
        }

        if (isset($col_order['col']) && $col_order['col'] != '') {
            if ($col_order['col'] == 'order_created_at') {
                $col_order['col'] = 'order.created_at';
            }
            $itemsCollection->getSelect()->order([$col_order['col'] . ' ' . $col_order['dir']]);
        }

        $data = ['count' => 0, 'collection' => []];
        $countCollection = clone $itemsCollection;
        $data['count'] = count($countCollection->getItems());
        unset($countCollection);
        
        $start = 0;
        $limit = $this->mp_dataHelper->getPageLength();
        if ($post_data['start'] > 0) {
            $start = (int) $post_data['start'];
        }
        $itemsCollection->getSelect()->limit($limit, $start);

        $orderConfig = $this->_configFactory->create();
        if ($data['count'] > 0) {
            foreach ($itemsCollection as $item) {

                $simple_data = $item->getData();
                $calculated_price = $item->getRowTotalInclTax() - $item->getDiscountAmount();

                $data['collection'][] = [
                    '<a target="_blank" href="' . $this->getUrl('*/order/orderView', ['order_id' => $item->getOrderId()]) . '" title="' . __('click to view detail') . '" >#' . $item->getIncrementId() . '</a>',
                    $this->_timezone->formatDate($item->getCreatedAt()),
                    $item->getFullName(),
                    (int) $item->getCustomQty(),
                    $orderConfig->getStatusLabel($simple_data['status']),
                    $this->mp_dataHelper->formatCurrency($calculated_price),
                    '<a href="' . $this->getUrl('*/order/printOrder', ['order_id' => $item->getOrderId()]) . '" target="_blank" title="' . __('click to print order') . '">' . __('Print Order') . '</a>'
                ];
            }
        } else {
            $data['collection'] = [];
        }
        unset($itemsCollection);
        return $data;
    }

}
