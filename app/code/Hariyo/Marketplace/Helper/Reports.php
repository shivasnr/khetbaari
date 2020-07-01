<?php

/**
 * Hariyo_Marketplace
 *
 * @category    Hariyo
 * @package     Hariyo_Marketplace
 * @author      Chet B. Sunar Team <shivasnr41@gmail.com>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Hariyo\Marketplace\Helper;
class Reports extends \Magento\Framework\App\Helper\AbstractHelper
{

    CONST REPORT_FORMAT_DAILY = 1;
    CONST REPORT_FORMAT_WEEKLY = 2;
    CONST REPORT_FORMAT_MONTHLY = 3;
    CONST REPORT_FORMAT_YEARLY = 4;

    private $_order_not_include = ['canceled', 'cancel_ogone', 'fraud', 'paypal_canceled_reversal', 'paypal_reversed', 'decline_ogone'];
    private $store_code = '';

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Model\Order\ItemFactory $orderItemFactory,
        \Magento\Sales\Model\Order $orderModel,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Sales\Model\Order\Status $orderStatusModel,
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configResource,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Hariyo\Marketplace\Model\Earnings $mpEarningsModel,
        \Hariyo\Marketplace\Model\Orderitem $mpOrderItemModel,
        \Hariyo\Marketplace\Model\Transactions $mpTransactionModel,
        \Hariyo\Marketplace\Helper\Product $mpProductHelper,
        \Hariyo\Marketplace\Helper\Setting $mpSettingHelper
    )
    {
        $this->mp_storeManager = $storeManager;
        $this->_orderItemFactory = $orderItemFactory;
        $this->_orderModel = $orderModel;
        $this->_priceHelper = $priceHelper;
        $this->_orderStatusModel = $orderStatusModel;
        $this->_eventManager = $eventManager;
        $this->mp_scopeConfig = $context->getScopeConfig();
        $this->mp_request = $context->getRequest();
        $this->mp_resource = $configResource;
        $this->mp_objectManager = $objectManager;
        $this->date = $date;
        $this->mp_earningsModel = $mpEarningsModel;
        $this->mp_orderItemModel = $mpOrderItemModel;
        $this->mp_transactionModel = $mpTransactionModel;
        $this->mp_productHelper = $mpProductHelper;
        $this->mp_settingHelper = $mpSettingHelper;
        $this->_customerSession = $customerSession;
        parent::__construct($context);
    }
    
    public function getAllReportFormat()
    {
        return [
            self::REPORT_FORMAT_DAILY => __('Daily'),
            self::REPORT_FORMAT_WEEKLY => __('Weekly'),
            self::REPORT_FORMAT_MONTHLY => __('Monthly'),
            self::REPORT_FORMAT_YEARLY => __('Yearly')
        ];
    }
    
    public function getOrderNotInclude()
    {
        return $this->_order_not_include;
    }
    
    public function getPercentageVariationReports($period)
    {
        $reportData = [];
        switch ($period) {
            case 'today':
                $today = date('Y-m-d', strtotime('today'));
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                $reportData['key'] = 'today';
                $reportData['data'] = $this->getFormattedReportData($today, $today, $yesterday, $yesterday);
                break;

            case 'week':
                $currentWeekStartDate = date('Y-m-d', strtotime('this week'));
                $currentWeekEndDate = date('Y-m-d', strtotime('this week +6 days'));
                $lastWeekStartDate = date('Y-m-d', strtotime('last week'));
                $lastWeekEndDate = date('Y-m-d', strtotime('last week +6 days'));
                $reportData['key'] = 'week';
                $reportData['data'] = $this->getFormattedReportData($currentWeekStartDate, $currentWeekEndDate, $lastWeekStartDate, $lastWeekEndDate);
                break;

            case 'month':
                $currentMonthStartDate = date('Y-m-d', strtotime('first day of this month'));
                $currentMonthEndDate = date('Y-m-d', strtotime('last day of this month'));
                $lastMonthStartDate = date('Y-m-d', strtotime('first day of last month'));
                $lastMonthEndDate = date('Y-m-d', strtotime('last day of last month'));
                $reportData['key'] = 'month';
                $reportData['data'] = $this->getFormattedReportData($currentMonthStartDate, $currentMonthEndDate, $lastMonthStartDate, $lastMonthEndDate);
                break;

            case 'year':
                $currentYear = date('Y', strtotime('this year'));
                $lastYear = date('Y', strtotime('last year'));
                $currentYearStartDate = date('Y-m-d', strtotime('first day of january ' . $currentYear . ''));
                $currentYearEndDate = date('Y-m-d', strtotime('last day of december ' . $currentYear . ''));
                $lastYearStartDate = date('Y-m-d', strtotime('first day of january ' . $lastYear . ''));
                $lastYearEndDate = date('Y-m-d', strtotime('last day of december ' . $lastYear . ''));
                $reportData['key'] = 'year';
                $reportData['data'] = $this->getFormattedReportData($currentYearStartDate, $currentYearEndDate, $lastYearStartDate, $lastYearEndDate);
                break;
        }
        return $reportData;
    }

    public function getAllowedOrderStatuses()
    {
        $orderStatusArray = $this->_orderStatusModel->getResourceCollection()->getData();
        $allowedStatuses = [];
        foreach ($orderStatusArray as $odrSt) {
            if (!in_array($odrSt['status'], $this->_order_not_include)) {
                $allowedStatuses[] = $odrSt['status'];
            }
        }
        return $allowedStatuses;
    }

    public function getFormattedReportData($firstStartDate, $firstEndDate, $secondStartDate, $secondEndDate) {
        $reportData = [];
        try {
            $sellerId = $this->_customerSession->getCustomer()->getId();

            $orderStatuses = $this->getStatusArrayToString($this->getAllowedOrderStatuses());
            $sellerProducts = $this->mp_productHelper->getSellerEnabledProducts($sellerId, $this->mp_storeManager->getWebsite()->getWebsiteId());
            $sellerProductsString = implode(',', $sellerProducts);

            
            
            $orderItemCollection = $this->_orderItemFactory->create()->getCollection();
            $orderItemCollection->getSelect()->join(['order' => $orderItemCollection->getTable('sales_order')], "main_table.order_id=order.entity_id");
            $orderItemCollection->getSelect()
                    ->join(
                            ['seller_product' => $orderItemCollection->getTable("vss_mp_product_to_seller")], "(main_table.product_id = seller_product.product_id and seller_product.seller_id=" . $sellerId . ")", []
            );

            $orderItemCollection->getSelect()
                    ->reset(\Zend_Db_Select::COLUMNS)
                    ->columns(['main_table.order_id'])
                    ->where('main_table.created_at >= "' . $firstStartDate . ' 00:00:00" and main_table.created_at <= "' . $firstEndDate . ' 23:59:59" and order.status in (' . $orderStatuses . ') and main_table.parent_item_id IS NULL')
                    ->group('main_table.order_id');

            $order_item_result = $orderItemCollection->getData();
            $reportData['orders_current']['value'] = sizeof($order_item_result);
            if (!empty($order_item_result)) {
                $reportData['orders_current']['found'] = 1;
            } else {
                $reportData['orders_current']['found'] = 0;
            }

            unset($orderItemCollection);

            $orderItemCollection = $this->_orderItemFactory->create()->getCollection();
            $orderItemCollection->getSelect()->join(['order' => $orderItemCollection->getTable('sales_order')], "main_table.order_id=order.entity_id");
            $orderItemCollection->getSelect()
                    ->join(
                            ['seller_product' => $orderItemCollection->getTable("vss_mp_product_to_seller")], "(main_table.product_id = seller_product.product_id and seller_product.seller_id=" . $sellerId . ")", []
            );
            $orderItemCollection->getSelect()
                    ->reset(\Zend_Db_Select::COLUMNS)
                    ->columns(['main_table.order_id'])
                    ->where('main_table.created_at >= "' . $secondStartDate . ' 00:00:00" and main_table.created_at <= "' . $secondEndDate . ' 23:59:59" and order.status in (' . $orderStatuses . ') and main_table.parent_item_id IS NULL')
                    ->group('main_table.order_id');
            
            $order_item_result = $orderItemCollection->getData();
            $reportData['orders_previous']['value'] = sizeof($order_item_result);
            if (!empty($order_item_result)) {
                $reportData['orders_previous']['found'] = 1;
            } else {
                $reportData['orders_previous']['found'] = 0;
            }
            unset($orderItemCollection);
            

            $reportData['order_diff'] = $reportData['orders_current']['value'] - $reportData['orders_previous']['value'];

            if ($reportData['orders_previous']['found'] == 0 || $reportData['orders_current']['found'] == 0) {
                $reportData['order_percentage'] = 'NA';
            } else {
                if ($reportData['orders_previous']['value'] == 0 && $reportData['orders_current']['value'] == 0) {
                    $reportData['order_percentage'] = 0;
                } else if ($reportData['orders_previous']['value'] == 0 || $reportData['orders_current']['value'] == 0) {
                    if ($reportData['orders_previous']['value'] == 0) {
                        $reportData['order_percentage'] = $reportData['orders_current']['value'] * 100;
                        $reportData['order_percentage'] = round($reportData['order_percentage'], 2);
                    } else {
                        $reportData['order_percentage'] = $reportData['orders_previous']['value'] * 100;
                        $reportData['order_percentage'] = round($reportData['order_percentage'], 2);
                    }
                } else {
                    if ($reportData['orders_previous']['value'] > $reportData['orders_current']['value']) {
                        $reportData['order_percentage'] = abs(($reportData['order_diff'] / $reportData['orders_previous']['value']) * 100);
                        $reportData['order_percentage'] = round($reportData['order_percentage'], 2);
                    } else if ($reportData['orders_previous']['value'] < $reportData['orders_current']['value']) {
                        $reportData['order_percentage'] = abs(($reportData['order_diff'] / $reportData['orders_current']['value']) * 100);
                        $reportData['order_percentage'] = round($reportData['order_percentage'], 2);
                    } else if ($reportData['orders_previous']['value'] == $reportData['orders_current']['value']) {
                        $reportData['order_percentage'] = 0;
                    }
                }
            }

            $orderItemCollection = $this->_orderItemFactory->create()->getCollection();
            $orderItemCollection->getSelect()->join(['order' => $orderItemCollection->getTable('sales_order')], "main_table.order_id=order.entity_id");
            $orderItemCollection->getSelect()
                    ->join(
                            ['seller_product' => $orderItemCollection->getTable("vss_mp_product_to_seller")], "(main_table.product_id = seller_product.product_id and seller_product.seller_id=" . $sellerId . ")", []
            );
            $orderItemCollection->getSelect()
                    ->reset(\Zend_Db_Select::COLUMNS)
                    ->columns(['main_table.product_id', 'main_table.created_at', 'main_table.qty_ordered', 'main_table.qty_canceled', 'main_table.row_total_incl_tax', 'main_table.discount_amount', '(main_table.row_total_incl_tax - main_table.discount_amount) as final_total'])
                    ->where('main_table.created_at >= "' . $firstStartDate . ' 00:00:00" and main_table.created_at <= "' . $firstEndDate . ' 23:59:59" and order.status in (' . $orderStatuses . ') and main_table.parent_item_id IS NULL');
            $itemData = $orderItemCollection->getData();
            $reportData['products_current']['value'] = 0;
            $reportData['revenue_current']['value'] = 0;
            $reportData['discount_current']['value'] = 0;

            if (!empty($itemData)) {
                $reportData['products_current']['found'] = 1;
                $reportData['revenue_current']['found'] = 1;
                $reportData['discount_current']['found'] = 1;
                foreach ($itemData as $item) {
                    $reportData['products_current']['value'] += ($item['qty_ordered'] - $item['qty_canceled']);
                    $reportData['revenue_current']['value'] += $item['final_total'];
                    $reportData['discount_current']['value'] += $item['discount_amount'];
                }
            } else {
                $reportData['products_current']['found'] = 0;
                $reportData['revenue_current']['found'] = 0;
                $reportData['discount_current']['found'] = 0;
            }

            $reportData['revenue_current_formatted'] = $this->_priceHelper->currency($reportData['revenue_current']['value'], true, false);
            $reportData['discount_current_formatted'] = $this->_priceHelper->currency($reportData['discount_current']['value'], true, false);
            unset($orderItemCollection);
            
            $orderItemCollection = $this->_orderItemFactory->create()->getCollection();
            $orderItemCollection->getSelect()->join(['order' => $orderItemCollection->getTable('sales_order')], "main_table.order_id=order.entity_id");
            $orderItemCollection->getSelect()
                    ->join(
                            ['seller_product' => $orderItemCollection->getTable("vss_mp_product_to_seller")], "(main_table.product_id = seller_product.product_id and seller_product.seller_id=" . $sellerId . ")", []
            );

            $orderItemCollection->getSelect()
                    ->reset(\Zend_Db_Select::COLUMNS)
                    ->columns(['main_table.product_id', 'main_table.qty_ordered', 'main_table.qty_canceled', 'main_table.row_total_incl_tax', 'main_table.discount_amount', '(main_table.row_total_incl_tax - main_table.discount_amount) as final_total'])
                    ->where('main_table.created_at >= "' . $secondStartDate . ' 00:00:00" and main_table.created_at <= "' . $secondEndDate . ' 23:59:59" and order.status in (' . $orderStatuses . ') and main_table.parent_item_id IS NULL');
            $itemData = $orderItemCollection->getData();
            unset($orderItemCollection);
            $reportData['products_previous']['value'] = 0;
            $reportData['revenue_previous']['value'] = 0;
            $reportData['discount_previous']['value'] = 0;
            if (!empty($itemData)) {
                $reportData['products_previous']['found'] = 1;
                $reportData['revenue_previous']['found'] = 1;
                $reportData['discount_previous']['found'] = 1;
                foreach ($itemData as $item) {
                    $reportData['products_previous']['value'] += ($item['qty_ordered'] - $item['qty_canceled']);
                    $reportData['revenue_previous']['value'] += $item['final_total'];
                    $reportData['discount_previous']['value'] += $item['discount_amount'];
                }
            } else {
                $reportData['products_previous']['found'] = 0;
                $reportData['revenue_previous']['found'] = 0;
                $reportData['discount_previous']['found'] = 0;
            }
            $reportData['revenue_previous_formatted'] = $this->_priceHelper->currency($reportData['revenue_previous']['value'], true, false);
            $reportData['discount_previous_formatted'] = $this->_priceHelper->currency($reportData['discount_previous']['value'], true, false);


            $reportData['product_diff'] = $reportData['products_current']['value'] - $reportData['products_previous']['value'];
            $reportData['revenue_diff'] = $reportData['revenue_current']['value'] - $reportData['revenue_previous']['value'];
            $reportData['discount_diff'] = $reportData['discount_current']['value'] - $reportData['discount_previous']['value'];

            if ($reportData['products_current']['found'] == 0 || $reportData['products_previous']['found'] == 0) {
                $reportData['product_percentage'] = 'NA';
            } else {
                if ($reportData['products_previous']['value'] == 0 && $reportData['products_current']['value'] == 0) {
                    $reportData['product_percentage'] = 0;
                } else if ($reportData['products_previous']['value'] == 0 || $reportData['products_current']['value'] == 0) {
                    if ($reportData['products_previous']['value'] == 0) {
                        $reportData['product_percentage'] = $reportData['products_current']['value'] * 100;
                        $reportData['product_percentage'] = round($reportData['product_percentage'], 2);
                    } else {
                        $reportData['product_percentage'] = $reportData['products_previous']['value'] * 100;
                        $reportData['product_percentage'] = round($reportData['product_percentage'], 2);
                    }
                } else {
                    if ($reportData['products_previous']['value'] > $reportData['products_current']['value']) {
                        $reportData['product_percentage'] = abs(($reportData['product_diff'] / $reportData['products_previous']['value']) * 100);
                        $reportData['product_percentage'] = round($reportData['product_percentage'], 2);
                    } else if ($reportData['products_previous']['value'] < $reportData['products_current']['value']) {
                        $reportData['product_percentage'] = abs(($reportData['product_diff'] / $reportData['products_current']['value']) * 100);
                        $reportData['product_percentage'] = round($reportData['product_percentage'], 2);
                    } else if ($reportData['products_previous']['value'] == $reportData['products_current']['value']) {
                        $reportData['product_percentage'] = 0;
                    }
                }
            }

            if ($reportData['revenue_current']['found'] == 0 || $reportData['revenue_previous']['found'] == 0) {
                $reportData['revenue_percentage'] = 'NA';
            } else {
                if ($reportData['revenue_previous']['value'] == 0 && $reportData['revenue_current']['value'] == 0) {
                    $reportData['revenue_percentage'] = 0;
                } else if ($reportData['revenue_previous']['value'] == 0 || $reportData['revenue_current']['value'] == 0) {
                    if ($reportData['revenue_previous']['value'] == 0) {
                        $reportData['revenue_percentage'] = $reportData['revenue_current']['value'] * 100;
                        $reportData['revenue_percentage'] = round($reportData['revenue_percentage'], 2);
                    } else {
                        $reportData['revenue_percentage'] = $reportData['revenue_previous']['value'] * 100;
                        $reportData['revenue_percentage'] = round($reportData['revenue_percentage'], 2);
                    }
                } else {
                    if ($reportData['revenue_previous']['value'] > $reportData['revenue_current']['value']) {
                        $reportData['revenue_percentage'] = abs(($reportData['revenue_diff'] / $reportData['revenue_previous']['value']) * 100);
                        $reportData['revenue_percentage'] = round($reportData['revenue_percentage'], 2);
                    } else if ($reportData['revenue_previous']['value'] < $reportData['revenue_current']['value']) {
                        $reportData['revenue_percentage'] = abs(($reportData['revenue_diff'] / $reportData['revenue_current']['value']) * 100);
                        $reportData['revenue_percentage'] = round($reportData['revenue_percentage'], 2);
                    } else if ($reportData['revenue_previous']['value'] == $reportData['revenue_current']['value']) {
                        $reportData['revenue_percentage'] = 0;
                    }
                }
            }

            if ($reportData['discount_current']['found'] == 0 || $reportData['discount_previous']['found'] == 0) {
                $reportData['discount_percentage'] = 'NA';
            } else {
                if ($reportData['discount_previous']['value'] == 0 && $reportData['discount_current']['value'] == 0) {
                    $reportData['discount_percentage'] = 0;
                } else if ($reportData['discount_previous']['value'] == 0 || $reportData['discount_current']['value'] == 0) {
                    if ($reportData['discount_previous']['value'] == 0) {
                        $reportData['discount_percentage'] = $reportData['discount_current']['value'] * 100;
                        $reportData['discount_percentage'] = round($reportData['discount_percentage'], 2);
                    } else {
                        $reportData['discount_percentage'] = $reportData['discount_previous']['value'] * 100;
                        $reportData['discount_percentage'] = round($reportData['discount_percentage'], 2);
                    }
                } else {
                    if ($reportData['discount_previous']['value'] > $reportData['discount_current']['value']) {
                        $reportData['discount_percentage'] = abs(($reportData['discount_diff'] / $reportData['discount_previous']['value']) * 100);
                        $reportData['discount_percentage'] = round($reportData['discount_percentage'], 2);
                    } else if ($reportData['discount_previous']['value'] < $reportData['discount_current']['value']) {
                        $reportData['discount_percentage'] = abs(($reportData['discount_diff'] / $reportData['discount_current']['value']) * 100);
                        $reportData['discount_percentage'] = round($reportData['discount_percentage'], 2);
                    } else if ($reportData['discount_previous']['value'] == $reportData['discount_current']['value']) {
                        $reportData['discount_percentage'] = 0;
                    }
                }
            }
        } catch (\Exception $ex) {
            $this->_logger->info($ex->getMessage());
        }
        return $reportData;
    }

    public function getDashBoardGraphData($start_date, $end_date)
    {
        try {
            $start = $start_date;
            $end = $end_date;
            $stats = [];
            $sellerId = $this->_customerSession->getCustomer()->getId();
            $default_data[0] = ['total_order' => 0, 'total_revenue' => 0, 'qty' => 0];
            while ($start <= $end) {
                $condition = "DATE(main_table.created_at) = '" . $start . "'";
                $collection = $this->_orderModel->getCollection();
                $collection->getSelect()
                        ->join(['order_item' => $collection->getTable('sales_order_item')], "main_table.entity_id=order_item.order_id");
                $collection->getSelect()
                        ->join(
                                ['seller_product' => $collection->getTable("vss_mp_product_to_seller")], "(order_item.product_id = seller_product.product_id and seller_product.seller_id=" . $sellerId . ")", []
                );
                $collection->getSelect()
                        ->reset(\Zend_Db_Select::COLUMNS)
                        ->columns([
                            'SUM(order_item.qty_ordered - order_item.qty_canceled) as qty',
                            'COUNT(distinct entity_id) as total_order',
                            'SUM((order_item.row_total_incl_tax - order_item.discount_amount)) as total_revenue'
                        ])
                        ->where("main_table.status IN (" . $this->getStatusArrayToString($this->getAllowedOrderStatuses()) . ") and order_item.parent_item_id IS NULL")
                        ->where($condition);
                $result = $collection->getData();
                if (empty($result) || !is_array($result)) {
                    $result = $default_data;
                }

                $result[0]['qty'] = (int) $result[0]['qty'];
                if ($result[0]['total_revenue'] == null || $result[0]['total_revenue'] == '') {
                    $result[0]['total_revenue'] = 0;
                }

                $result[0]['formatted_total_revenue'] = $this->_priceHelper->currency($result[0]['total_revenue'], true, false);
                $temp = $result[0];
                $temp['xaxis'] = date('d-M', strtotime($start));
                $stats[] = $temp;
                $start = date('Y-m-d', strtotime('1 day', strtotime($start)));
            }
        } catch (\Exception $ex) {
            $this->_logger->info($ex->getMesage());
            
        }
        return $stats;
        
    }

    public function getStatusArrayToString($statuses = [])
    {
        $str = '';
        foreach ($statuses as $val) {
            $str .= "'" . $val . "',";
        }
        return rtrim($str, ',');
    }

    public function getSellerProductFromOrder($order_id)
    {
        try {
            $itemsCollection = $this->_orderItemFactory->create()->getCollection();
            $itemsCollection->getSelect()->join(['s2p' => $itemsCollection->getTable('vss_mp_product_to_seller')], 'main_table.product_id = s2p.product_id');
            $itemsCollection->addExpressionFieldToSelect("custom_qty", 'SUM({{main_table.qty_ordered}} - {{main_table.qty_canceled}})', ["main_table.qty_ordered" => "main_table.qty_ordered", "main_table.qty_canceled" => "main_table.qty_canceled"]);
            $itemsCollection->addExpressionFieldToSelect("row_total_incl_tax", 'SUM({{main_table.row_total_incl_tax}})', ["main_table.row_total_incl_tax" => "main_table.row_total_incl_tax"]);
            $itemsCollection->addExpressionFieldToSelect("discount_amount", 'SUM({{main_table.discount_amount}})', ["main_table.discount_amount" => "main_table.discount_amount"]);

            $itemsCollection->addAttributeToFilter('main_table.parent_item_id', ['null' => true]);
            $itemsCollection->addAttributeToFilter('main_table.order_id', ['eq' => $order_id]);
            $itemsCollection->getSelect()->group("main_table.sku");
            $seller_products = [];
            $index = 0;
            if ($itemsCollection->getSize() > 0) {
                foreach ($itemsCollection as $item) {
                    if ($item->getParentItem())
                        continue;
                    $seller_products[$item->getSellerId()][$index] = $this->getOrderItemDetail($item);
                    $index++;
                }
                if (!empty($seller_products)) {
                    return $seller_products;
                }
            }
            unset($itemsCollection);
        } catch (\Exception $ex) {
            $this->_logger->info($ex->getMesage());
        }
        return false;
    }

    public function getOrderItemDetail($item)
    {
        $data = [];
        $data['order_id'] = $item->getOrderId();
        $data['name'] = $item->getName();
        $data['sku'] = $item->getSku();
        $data['item_id'] = $item->getId();
        $data['product_id'] = $item->getProductId();

        if ($options = $this->getItemOptions($item)) {
            $option_to_save = [];
            foreach ($options as $option) {
                $option_to_save[] = ['label' => $option['label'], 'value' => $option['value']];
            }
            $data['options'] = $option_to_save;
        }

        $data['qty'] = $item->getQtyOrdered() * 1;

        $data['row_total'] = $data['sub_total'] = $item->getRowTotalInclTax();
        $data['discount'] = $item->getDiscountAmount();
        if ($data['discount'] > 0) {
            $data['row_total'] = $data['row_total'] - $data['discount'];
        }

        return $data;
    }

    private function getItemOptions($item)
    {
        $result = [];
        if ($options = $item->getProductOptions()) {
            if (isset($options['options'])) {
                $result = array_merge($result, $options['options']);
            }
            if (isset($options['additional_options'])) {
                $result = array_merge($result, $options['additional_options']);
            }
            if (isset($options['attributes_info'])) {
                $result = array_merge($result, $options['attributes_info']);
            }
        }
        return $result;
    }

    /*
     * Function to calculate and save seller earning and admin commission
     * @param int $seller_id
     * @param int $order_id
     * @param array $seller_ordered_products
     */
    
    public function saveEarningAndCommission($seller_id, $order_id, $seller_ordered_products, $isNew = true)
    {
        $earning_col = null;
        $commision_percent = 0;
        try {
            if (!$isNew) {
                $model = $this->_orderModel->load($order_id);
                $tmp = $model->getData();
                $model->unsetData();
                $original_order_model = $this->_orderModel->load($tmp['original_increment_id'], 'increment_id');
                $original_order = $original_order_model->getData();
                $original_order_model->unsetData();
                $earning_col = $this->mp_earningsModel->load($original_order['entity_id'], 'order_id');
                $commision_percent = $earning_col->getData('commision_percent');
                $order_id = $tmp['entity_id'];
            } else {
                $commision_percent = (float) $this->mp_settingHelper->getSettingByKey($seller_id, 'commission');
            }

            $total_earning = 0;
            $seller_earning = 0;
            $admin_earning = 0;
            $product_count = 0;
            foreach ($seller_ordered_products as $item) {
                try {
                    if ($isNew) {
                        $this->_eventManager->dispatch('update_commission_percent', ['commision_percent' => &$commision_percent, 'order_item' => $item]);
                    }
                    $product_count = $product_count + $item['qty'];
                    $total_earning = $total_earning + (float) $item['row_total'];
                    $admin_earning = $admin_earning + (float) ((float) ($commision_percent / 100) * (float) $item['row_total']);
                } catch (\Exception $e) {
                    $total_earning = 0;
                    $seller_earning = 0;
                    $admin_earning = 0;
                    $product_count = 0;
                    $message = 'Failed to calculate earnings after order placing for order id (#' . $order_id . ') with commision(' . $commision_percent . '%). Error - ' . $e->getMessage();
                    $this->_logger->info($message);
                }
            }
            $seller_earning = $total_earning - $admin_earning;
            $currency_code = $this->mp_storeManager->getStore()->getCurrentCurrencyCode();

            if (!$isNew) {
                $earning_col->setOrderId($order_id);
                $earning_col->setCurrencyCode($currency_code);
                $earning_col->setProductCount($product_count);
                $earning_col->setTotalEarning($total_earning);
                $earning_col->setSellerEarning($seller_earning);
                $earning_col->setAdminComission($admin_earning);
                $earning_col->setUpdatedAt($this->mp_settingHelper->getDate());
                $earning_col->save();
                $earning_col->unsetData();
            } else {
                $earning_col = $this->mp_earningsModel;
                $data = [
                    'seller_id' => $seller_id,
                    'website_id' => $this->mp_storeManager->getStore()->getWebsiteId(),
                    'store_id' => $this->mp_storeManager->getStore()->getId(),
                    'order_id' => $order_id,
                    'currency_code' => $currency_code,
                    'product_count' => $product_count,
                    'commision_percent' => $commision_percent,
                    'total_earning' => $total_earning,
                    'seller_earning' => $seller_earning,
                    'admin_comission' => $admin_earning,
                    'created_at' => $this->mp_settingHelper->getDate(),
                    'updated_at' => $this->mp_settingHelper->getDate()
                ];
                $earning_col->addData($data);
                $earning_col->save();
                $earning_col->unsetData();
            }

            $final_data = array(
                'seller_id' => $seller_id,
                'website_id' => $this->mp_storeManager->getStore()->getWebsiteId(),
                'store_id' => $this->mp_storeManager->getStore()->getId(),
                'order_id' => $order_id,
                'currency_code' => $currency_code,
                'product_count' => $product_count,
                'commision_percent' => $commision_percent,
                'total_earning' => $total_earning,
                'seller_earning' => $seller_earning,
                'admin_comission' => $admin_earning,
                'order_items' => $seller_ordered_products,
                'is_new' => $isNew
            );

            $this->_eventManager->dispatch('seller_earning_save_after', ['earning_data' => $final_data]);
        } catch (\Exception $e) {
            $message = 'Failed to save earning and commission for order(#' . $order_id . ') with commsion(' . $commision_percent . '%). Error - ' . $e->getMessage();
            $this->_logger->info($message);  
        }
    }

    public function getBalanceVariationData($period)
    {
        $reportData = [];
        switch ($period) {
            case 'today':
                $today = date('Y-m-d', strtotime('today'));
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                $reportData = $this->getFormattedBalanceVariationData($today, $today, $yesterday, $yesterday);
                break;

            case 'week':
                $currentWeekStartDate = date('Y-m-d', strtotime('this week'));
                $currentWeekEndDate = date('Y-m-d', strtotime('this week +6 days'));
                $lastWeekStartDate = date('Y-m-d', strtotime('last week'));
                $lastWeekEndDate = date('Y-m-d', strtotime('last week +6 days'));
                $reportData = $this->getFormattedBalanceVariationData($currentWeekStartDate, $currentWeekEndDate, $lastWeekStartDate, $lastWeekEndDate);
                break;

            case 'month':
                $currentMonthStartDate = date('Y-m-d', strtotime('first day of this month'));
                $currentMonthEndDate = date('Y-m-d', strtotime('last day of this month'));
                $lastMonthStartDate = date('Y-m-d', strtotime('first day of last month'));
                $lastMonthEndDate = date('Y-m-d', strtotime('last day of last month'));
                $reportData = $this->getFormattedBalanceVariationData($currentMonthStartDate, $currentMonthEndDate, $lastMonthStartDate, $lastMonthEndDate);
                break;

            case 'year':
                $currentYear = date('Y', strtotime('this year'));
                $lastYear = date('Y', strtotime('last year'));
                $currentYearStartDate = date('Y-m-d', strtotime('first day of january ' . $currentYear . ''));
                $currentYearEndDate = date('Y-m-d', strtotime('last day of december ' . $currentYear . ''));
                $lastYearStartDate = date('Y-m-d', strtotime('first day of january ' . $lastYear . ''));
                $lastYearEndDate = date('Y-m-d', strtotime('last day of december ' . $lastYear . ''));
                $reportData = $this->getFormattedBalanceVariationData($currentYearStartDate, $currentYearEndDate, $lastYearStartDate, $lastYearEndDate);
                break;
        }
        return $reportData;
    }

    public function getFormattedBalanceVariationData($firstStartDate, $firstEndDate, $secondStartDate, $secondEndDate)
    {
        $reportData = [];
        $tempData = [];
        try {
            $sellerId = $this->_customerSession->getCustomer()->getId();
            $transCollection = $this->mp_transactionModel->getCollection();
            $transCollection->getSelect()
                    ->reset(\Zend_Db_Select::COLUMNS)
                    ->columns(['SUM(amount) as amount'])
                    ->where('main_table.created_at >= "' . $firstStartDate . ' 00:00:00" and main_table.created_at <= "' . $firstEndDate . ' 23:59:59" and seller_id=' . $sellerId)
                    ->group('seller_id');

            $tempData = $transCollection->getData();
            if (isset($tempData[0]['amount'])) {
                $reportData['amount_current']['value'] = $tempData[0]['amount'];
            } else {
                $reportData['amount_current']['value'] = 0;
            }
            if (!empty($tempData)) {
                $reportData['amount_current']['found'] = 1;
            } else {
                $reportData['amount_current']['found'] = 0;
            }

            unset($transCollection);

            $transCollection = $this->mp_transactionModel->getCollection();
            $transCollection->getSelect()
                    ->reset(\Zend_Db_Select::COLUMNS)
                    ->columns(['SUM(amount) as amount'])
                    ->where('main_table.created_at >= "' . $secondStartDate . ' 00:00:00" and main_table.created_at <= "' . $secondEndDate . ' 23:59:59" and seller_id=' . $sellerId)
                    ->group('seller_id');

            $tempData = $transCollection->getData();
            if (isset($tempData[0]['amount'])) {
                $reportData['amount_previous']['value'] = $tempData[0]['amount'];
            } else {
                $reportData['amount_previous']['value'] = 0;
            }
            if (!empty($tempData)) {
                $reportData['amount_previous']['found'] = 1;
            } else {
                $reportData['amount_previous']['found'] = 0;
            }

            unset($transCollection);

            $reportData['amount_previous_formatted'] = $this->_priceHelper->currency($reportData['amount_previous']['value'], true, false);
            $reportData['amount_current_formatted'] = $this->_priceHelper->currency($reportData['amount_current']['value'], true, false);
            $reportData['amount_diff'] = $reportData['amount_current']['value'] - $reportData['amount_previous']['value'];

            if ($reportData['amount_current']['found'] == 0 || $reportData['amount_previous']['found'] == 0) {
                $reportData['amount_percentage'] = 'NA';
            } else {
                if ($reportData['amount_previous']['value'] == 0 && $reportData['amount_current']['value'] == 0) {
                    $reportData['amount_percentage'] = 0;
                } else if ($reportData['amount_previous']['value'] == 0 || $reportData['amount_current']['value'] == 0) {
                    if ($reportData['amount_previous']['value'] == 0) {
                        $reportData['amount_percentage'] = $reportData['amount_current']['value'] * 100;
                        $reportData['amount_percentage'] = round($reportData['amount_percentage'], 2);
                    } else {
                        $reportData['amount_percentage'] = $reportData['amount_previous']['value'] * 100;
                        $reportData['amount_percentage'] = round($reportData['amount_percentage'], 2);
                    }
                } else {
                    if ($reportData['amount_previous']['value'] > $reportData['amount_current']['value']) {
                        $reportData['amount_percentage'] = abs(($reportData['amount_diff'] / $reportData['amount_previous']['value']) * 100);
                        $reportData['amount_percentage'] = round($reportData['amount_percentage'], 2);
                    } else if ($reportData['amount_previous']['value'] < $reportData['amount_current']['value']) {
                        $reportData['amount_percentage'] = abs(($reportData['amount_diff'] / $reportData['amount_current']['value']) * 100);
                        $reportData['amount_percentage'] = round($reportData['amount_percentage'], 2);
                    } else if ($reportData['amount_previous']['value'] == $reportData['amount_current']['value']) {
                        $reportData['amount_percentage'] = 0;
                    }
                }
            }
        } catch (\Exception $ex) {
            $this->_logger->info($ex->getMesage());
        }
        return $reportData;
    }

    public function updateEarningOnCancelOrder($order_id)
    {
        try {
            $earning_col = $this->mp_earningsModel->load($order_id, 'order_id');
            $earning_col_data = $earning_col->getData();
            if (!empty($earning_col_data)) {
                $data = [
                    'seller_earning' => 0,
                    'admin_comission' => 0,
                    'status' => 1,
                    'updated_at' => $this->mp_settingHelper->getData()
                ];
                $earning_col->addData($data);
                $earning_col->save();
                $earning_col->unsetData();
                $this->_eventManager->dispatch('update_seller_earning_on_cancel', ['earning_data' => $data]);
            }
        } catch (\Exception $e) {
            $message = 'Failed to update earning and commission on canceling order(#' . $order_id . '). Error - ' . $e->getMessage();
            $this->_logger->info($message);
        }
    }

    public function updateOrderItemsOnCancelOrder($order_id)
    {
        try {
            $data = ['consider_for_calculations' => \Hariyo\Marketplace\Helper\Product::CALCULATION_NOT_ALLOWED];
            $model = $this->mp_orderItemModel->getCollection()->addFieldToFilter('order_id', $order_id);
            if($model->getSize() > 0){
                $model_data = $model->getData();
                unset($model);
                foreach ($model_data as $orderModel) {
                    $updateModel = $this->mp_orderItemModel->load($orderModel['row_id'])->addData($data);
                    $updateModel->setId($orderModel['row_id'])->save();

                    $this->_eventManager->dispatch('update_order_item_on_cancel', ['order_id' => $order_id, 'consider_for_calculation' => \Hariyo\Marketplace\Helper\Product::CALCULATION_NOT_ALLOWED]);
                    $updateModel->unsetData();
                }
            }
        } catch (\Exception $e) {
            $message = 'Failed to update order item on canceling order(#' . $order_id . '). Error - ' . $e->getMessage();
            $this->_logger->info($message);
        }
    }

}
