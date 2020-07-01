<?php

namespace Knowband\Marketplace\Block\Earnings;

class Summary extends \Magento\Framework\View\Element\Template {

    private $collection = [
        'total_sale' => 0,
        'total_earning' => 0,
        'total_order' => 0,
        'product_count' => 0
    ];

    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Knowband\Marketplace\Model\Earnings $mpEarningsModel,
            \Knowband\Marketplace\Helper\Data $mpDataHelper
    ) {
        $this->_objectManager = $objectManager;
        $this->mp_earningsModel = $mpEarningsModel;
        $this->mp_dataHelper = $mpDataHelper;
        parent::__construct($context);
        
        $seller_info = $this->mp_dataHelper->getSellerInfo();
        $reportHelper = $this->_objectManager->get("\Knowband\Marketplace\Helper\Reports");
        $collection = $this->mp_earningsModel->getCollection();
        $collection->getSelect()->join(['o' => $collection->getTable('sales_order')], 'o.entity_id = main_table.order_id');
        $collection->addFieldToFilter('o.status', ['nin' => $reportHelper->getOrderNotInclude()]);
        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS)
                ->columns([
                    "SUM(main_table.total_earning) as total_sale",
                    "SUM(main_table.seller_earning) as total_earning",
                    "COUNT(main_table.order_id) as total_order",
                    "SUM(main_table.product_count) as product_count",
        ]);
        $collection->addFieldToFilter('main_table.seller_id', ['eq' => $seller_info['entity_id']]);

        if ($collection->getSize() > 0) {
            $tmp = $collection->getData();
            $this->collection = $tmp[0];
        }
        unset($collection);
    }
    
    public function getTotalSale() {
        return $this->mp_dataHelper->formatCurrency($this->collection['total_sale']);
    }

    public function getTotalEarning() {
        return $this->mp_dataHelper->formatCurrency($this->collection['total_earning']);
    }

    public function getTotalOrder() {
        return $this->collection['total_order'];
    }

    public function getTotalProductSold() {
        return $this->collection['product_count'];
    }

}
