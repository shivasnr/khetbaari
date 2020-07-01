<?php

namespace Knowband\Marketplace\Block\Seller;

class Dashboard extends \Magento\Framework\View\Element\Template {

    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Magento\Sales\Model\Order\ItemFactory $orderItemFactory,
            \Magento\Sales\Model\Order $orderModel,
            \Magento\Framework\Pricing\Helper\Data $priceHelper,
            \Magento\Framework\Json\Helper\Data $jsonHelper,
            \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
            \Knowband\Marketplace\Model\Product $mpProductToSellerModel,
            \Knowband\Marketplace\Helper\Setting $mpSettingHelper,
            \Knowband\Marketplace\Helper\Data $mpDataHelper,
            \Knowband\Marketplace\Helper\Reports $mpReportsHelper
    ) {
        $this->_orderItemFactory = $orderItemFactory;
        $this->_objectManager = $objectManager;
        $this->_orderModel = $orderModel;
        $this->_priceHelper = $priceHelper;
        $this->_jsonHelper = $jsonHelper;
        $this->_timezone = $timezone;
        $this->mp_productToSellerModel = $mpProductToSellerModel;
        $this->mp_settingHelper = $mpSettingHelper;
        $this->mp_dataHelper = $mpDataHelper;
        $this->mp_reportsHelper = $mpReportsHelper;
        parent::__construct($context);
    }
    
    public function getFrontUrl($controller, $action = null, $params = []){
        return $this->mp_dataHelper->getFrontUrl($controller, $action, $params);
    }
    
    public function getRevenueSummary() {
        $seller_info = $this->mp_dataHelper->getSellerInfo();
        $orderStatuses = $this->mp_reportsHelper->getStatusArrayToString($this->mp_reportsHelper->getAllowedOrderStatuses());
        $collection = $this->_orderItemFactory->create()->getCollection();
        $collection->getSelect()->join(['order' => $collection->getTable('sales_order')], "main_table.order_id=order.entity_id");
        $collection->getSelect()
                ->join(
                        ['seller_product' => $collection->getTable("vss_mp_product_to_seller")], 
                        "(main_table.product_id = seller_product.product_id and seller_product.seller_id=" . $seller_info['entity_id'] . ")", 
                        [] 
                );
       
        
        $collection->getSelect()
                ->reset(\Zend_Db_Select::COLUMNS)
                ->columns(['main_table.product_id', 'main_table.qty_ordered', 'main_table.qty_canceled', 'main_table.row_total_incl_tax', 'main_table.discount_amount', '(main_table.row_total_incl_tax - main_table.discount_amount) as final_total'])
                ->where('order.status in (' . $orderStatuses . ') and main_table.parent_item_id IS NULL');
        
        $itemData = $collection->getData();
        unset($collection);
        
        $summary = [
            'total_products' => 0,
            'total_revenue' => 0
        ];
        foreach ($itemData as $item) {
            $summary['total_products'] += ($item['qty_ordered'] - $item['qty_canceled']);
            $summary['total_revenue'] += $item['final_total'];
        }
        $summary['total_revenue_formatted'] = $this->_priceHelper->currency($summary['total_revenue'], true, false);

        $collection = $this->_orderItemFactory->create()->getCollection();
        $collection->getSelect()
                ->join(
                        ['p2s' => $collection->getTable('vss_mp_product_to_seller')], 
                        "(main_table.product_id = p2s.product_id and p2s.seller_id=" . $seller_info['entity_id'] . ")", 
                        []
                );
        $collection->getSelect()->join(['order' => $collection->getTable('sales_order')], "main_table.order_id=order.entity_id");

        $collection->getSelect()
                ->reset(\Zend_Db_Select::COLUMNS)
                ->columns(['COUNT(main_table.order_id) as total_order'])
                ->where('order.status in (' . $orderStatuses . ')');

        $summary['total_orders'] = (int) $collection->getFirstItem()->getTotalOrder();
        unset($collection);
        return $summary;
    }

    public function getGraphData()
    {
        $currentWeekStartDate = date('Y-m-d', strtotime('today -6 days'));
        $currentWeekEndDate = date('Y-m-d', strtotime('today'));
        $result = $this->mp_reportsHelper->getDashBoardGraphData($currentWeekStartDate, $currentWeekEndDate);
        return $this->_jsonHelper->jsonEncode($result);
    }
    
    public function getSaleImprovementReport()
    {
        $report = [];
        $report['today'] = $this->mp_reportsHelper->getPercentageVariationReports('today');
        $report['week'] = $this->mp_reportsHelper->getPercentageVariationReports('week');
        $report['month'] = $this->mp_reportsHelper->getPercentageVariationReports('month');
        $report['year'] = $this->mp_reportsHelper->getPercentageVariationReports('year');
        $summary = [
            ['title' => __('Today'), 'prev_label' => __('Yesterday'), 'report' => $report['today']],
            ['title' => __('This Week'), 'prev_label' => __('Last Week'), 'report' => $report['week']],
            ['title' => __('This Month'), 'prev_label' => __('Last Month'), 'report' => $report['month']],
            ['title' => __('This Year'), 'prev_label' => __('Last Year'), 'report' => $report['year']]
        ];
        return $summary;
    }
    
    public function getBalanceVariationReport()
    {
        $report = [];
        $report['today'] = $this->mp_reportsHelper->getBalanceVariationData('today');
        $report['week'] = $this->mp_reportsHelper->getBalanceVariationData('week');
        $report['month'] = $this->mp_reportsHelper->getBalanceVariationData('month');
        $report['year'] = $this->mp_reportsHelper->getBalanceVariationData('year');
        return $report;
    }
    
    public function getRecentOrders()
    {
        $seller_info = $this->mp_dataHelper->getSellerInfo();
        $orderStatuses = $this->mp_reportsHelper->getStatusArrayToString($this->mp_reportsHelper->getAllowedOrderStatuses());
        $collection = $this->_orderItemFactory->create()->getCollection();
        $collection->getSelect()->join(['order' => $collection->getTable('sales_order')], "main_table.order_id=order.entity_id");
        $collection->getSelect()
            ->join(
                ['p2s' => $collection->getTable('vss_mp_product_to_seller')],
                "(main_table.product_id = p2s.product_id and p2s.seller_id=" . $seller_info['entity_id'] . ")",
                []
            );
        $collection->getSelect()
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns(['main_table.order_id'])
            ->where('order.status in (' . $orderStatuses . ')')
            ->group('main_table.order_id')
            ->order('main_table.created_at desc');
        $collection->getSelect()->limit(10);
        
        $orderIds = $collection->getData();
        unset($collection);
        if (empty($orderIds)) {
            return [];
        }
        
        $seller_product = $this->mp_productToSellerModel;
        $orders = [];
        foreach ($orderIds as $val) {
            $temp = [];
            $order = $this->_orderModel->load($val['order_id']);
            $temp['order_id'] = $val['order_id'];
            $temp['order_date'] = $this->_timezone->formatDate($order->getCreatedAt());
            $temp['order_email'] = $order->getCustomerEmail();
            $temp['customer_name'] = $order->getCustomerName();
            $temp['order_number'] = $order->getIncrementId();
            $temp['order_status'] = $order->getStatusLabel();
            $temp['shipping_charges'] = $order->formatPrice($order->getShippingInclTax());
            $temp['qty'] = 0;
            $temp['total'] = 0;
            
            $orderItems = $order->getAllVisibleItems();
            $order->unsetData();
            
            foreach ($orderItems as $sItem) {
                if ($sItem->isDummy()){
                    continue;
                }
                $seller_product->unsetData();
                if (!$this->_objectManager->get('\Knowband\Marketplace\Helper\Product')->isSellerProduct($sItem->getProductId(), $seller_info['entity_id'])) {
                    continue;
                }
                $temp['qty'] += $temp['qty'] + $sItem->getQtyOrdered();
                $temp['total'] += $temp['total'] + ($sItem->getRowTotalInclTax() - $sItem->getDiscountAmount());
            }
            $temp['total'] = $this->_priceHelper->currency($temp['total'], true, false);
            $orders[] = $temp;
        }
        
        return $orders;
    }
}
