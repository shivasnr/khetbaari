<?php

namespace Hariyo\Marketplace\Block\Earnings;

class Orderwise extends \Magento\Framework\View\Element\Template {

    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Sales\Model\Order\ConfigFactory $configFactory,
            \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
            \Hariyo\Marketplace\Model\Earnings $mpEarningsModel,
            \Hariyo\Marketplace\Helper\Data $mpDataHelper
    ) {
        $this->_configFactory = $configFactory;
        $this->_timezone = $timezone;
        $this->mp_dataHelper = $mpDataHelper;
        $this->mp_earningsModel = $mpEarningsModel;
        parent::__construct($context);
    }
    
    public function getFieldId() {
        return 'vssmp_seller_earning_orderwise';
    }

    public function getFilters() {
        $orderConfig = $this->_configFactory->create();
        return [
            ['label' => __('From Date'), 'name' => 'ow_from_date', 'className' => 'form-control has_datepicker', 'type' => 'date', 'values' => ''],
            ['label' => __('To Date'), 'name' => 'ow_to_date', 'className' => 'form-control has_datepicker', 'type' => 'date', 'values' => ''],
            ['label' => __('Status'), 'name' => 'status', 'className' => 'form-control', 'type' => 'select', 'values' => $orderConfig->getStatuses()]
        ];
    }

    public function getColumns() {
        return [
            ['label' => __('Order No.'), 'name' => 'o.increment_id', 'className' => 'vssmp-txt-ryt', 'targets' => 0, 'width' => ''],
            ['label' => __('Order Date'), 'name' => 'main_table.created_at', 'className' => '', 'targets' => 1, 'width' => ''],
            ['label' => __('Total Products Sold'), 'name' => 'main_table.product_count', 'className' => 'vssmp-txt-ryt', 'targets' => 2, 'width' => ''],
            ['label' => __('Order Total'), 'name' => 'main_table.total_earning', 'className' => 'vssmp-txt-ryt', 'targets' => 3, 'width' => ''],
            ['label' => __('Status'), 'name' => 'o.status', 'className' => '', 'targets' => 4, 'width' => ''],
            ['label' => __('Your Earnings'), 'name' => 'main_table.seller_earning', 'className' => 'vssmp-txt-ryt', 'targets' => 5, 'width' => '']
        ];
    }

    public function getListUrl() {
        return $this->mp_dataHelper->getFrontUrl('earnings', 'getAjaxData', ['action' => 'orderwise']);
    }

    public function getList() {
        $seller_info = $this->mp_dataHelper->getSellerInfo();
        $post_data = $this->getRequest()->getParams();
        $col_order = $this->mp_dataHelper->getColOrder($post_data);

        $collection = $this->mp_earningsModel->getCollection();
        $collection->getSelect()->join(['o' => $collection->getTable("sales_order")], 'main_table.order_id = o.entity_id');
        $collection->addFieldToFilter('main_table.seller_id', ['eq' => $seller_info['entity_id']]);

        $filters = $this->getFilters();
        foreach ($filters as $filter) {
            if (isset($post_data[$filter['name']]) && $post_data[$filter['name']] != '') {
                if ($filter['type'] == 'select' || $filter['type'] == 'date') {
                    if ($filter['type'] == 'date' && $filter['name'] == 'ow_from_date') {
                        $formatted_date = date(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT, strtotime($post_data[$filter['name']]));
                        $collection->getSelect()->where('DATE(main_table.created_at) >= "' . $formatted_date . '"');
                    } else if ($filter['type'] == 'date' && $filter['name'] == 'ow_to_date') {
                        $formatted_date = date(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT, strtotime($post_data[$filter['name']]));
                        $collection->getSelect()->where('DATE(main_table.created_at) <= "' . $formatted_date . '"');
                    } else {
                        if ($filter['name'] == 'status') {
                            $collection->addFieldToFilter('o.status', ['eq' => $post_data[$filter['name']]]);
                        } else {
                            $collection->addFieldToFilter($filter['name'], ['eq' => $post_data[$filter['name']]]);
                        }
                    }
                } else if ($filter['type'] == 'text') {
                    if ($filter['name'] == 'status') {
                        $collection->addFieldToFilter('o.status', ['eq' => $post_data[$filter['name']]]);
                    } else {
                        $collection->addFieldToFilter($filter['name'], ['like' => '%' . $post_data[$filter['name']] . '%']);
                    }
                }
            }
        }

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS)
                ->columns(["main_table.*", "o.increment_id", "o.status"]);

        if (isset($col_order['col']) && $col_order['col'] != '') {
            $collection->getSelect()->order([$col_order['col'] . ' ' . $col_order['dir']]);
        } else {
            $collection->getSelect()->order(['o.entity_id DESC']);
        }

        $data = [];
        $countCollection = clone $collection;
        $data['count'] = count($countCollection->getData());

        unset($countCollection);
        $start = 0;
        $limit = $this->mp_dataHelper->getPageLength();
        if ($post_data['start'] > 0) {
            $start = (int) $post_data['start'];
        }
        $collection->getSelect()->limit($limit, $start);

        $orderConfig = $this->_configFactory->create();
        if ($data['count'] > 0) {
            foreach ($collection as $item) {
                $data['collection'][] = [
                    '<a target="_blank" href="' . $this->getUrl('*/order/orderview', ['order_id' => $item->getOrderId()]) . '" title="' . __('click to view detail') . '" >#' . $item->getIncrementId() . '</a>',
                    $this->_timezone->formatDate($item->getCreatedAt()),
                    (int) $item->getProductCount(),
                    $this->mp_dataHelper->formatCurrency($item->getTotalEarning()),
                    $orderConfig->getStatusLabel($item->getStatus()),
                    $this->mp_dataHelper->formatCurrency($item->getSellerEarning()),
                ];
            }
        } else {
            $data['collection'] = [];
        }
        unset($collection);
        return $data;
    }

}
