<?php

namespace Knowband\Marketplace\Block\Order\View;

class Shipments extends \Knowband\Marketplace\Block\Order\General {
    protected $order = null;
    
    protected $total_shipments = 0;
    
    protected $shipments = array();
    
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Magento\Framework\Registry $registry,
            \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
            \Magento\Catalog\Model\Product $productModel,
            \Magento\Framework\Event\Manager $eventManager,
            \Knowband\Marketplace\Model\Seller $mpSellerModel,
            \Knowband\Marketplace\Model\Shipments $mpShipmentModel,
            \Knowband\Marketplace\Helper\Data $mpDataHelper,
            \Knowband\Marketplace\Helper\Log $mpLogHelper,
            \Magento\Sales\Model\ResourceModel\Order\Shipment\Grid\CollectionFactory $shipmentGridCollectionFactory
    ) {
        $this->_coreRegistry = $registry;
        $this->_objectManager = $objectManager;
        $this->_timezone = $timezone;
        $this->mp_dataHelper = $mpDataHelper;
        $this->_shipmentCollectionFactory = $shipmentGridCollectionFactory;
        parent::__construct($context, $objectManager, $registry, $timezone, $productModel, $eventManager, $mpSellerModel, $mpShipmentModel, $mpDataHelper, $mpLogHelper);
    }

    protected function _beforeToHtml() {
        $this->order = $this->_coreRegistry->registry('current_order');
        $this->_initShipments();
        return parent::_beforeToHtml();
    }

    public function _initShipments() {
        $seller_info = $this->mp_dataHelper->getSellerInfo();
        $collection = $this->_shipmentCollectionFactory->create()
                ->addFieldToSelect('entity_id')
                ->addFieldToSelect('created_at')
                ->addFieldToSelect('increment_id')
                ->addFieldToSelect('total_qty')
                ->addFieldToSelect('shipping_name')
                ->addFieldToFilter('order_id',['eq' => $this->order->getId()]);

        $collection->getSelect()
            ->join(
                ['s2ship' => $collection->getTable("vss_mp_seller_shipments")], "(main_table.entity_id = s2ship.shipment_id and s2ship.seller_id=" . $seller_info['entity_id'] . ")", []
        );
        
//        $collection->getSelect()
//            ->join(
//                ['s2grid' => $collection->getTable("sales_shipment_grid")], "(main_table.entity_id = s2grid.entity_id)", ['s2grid.shipping_name', 's2grid_order_id' => 's2grid.order_id']
//        );

        $invoices = $collection->load()->toArray();
        
        unset($collection);
        

        $this->total_shipments = (int) $invoices['totalRecords'];

        $this->shipments = $invoices['items'];
    }

    public function getShipments() {
        return $this->shipments;
    }

    public function getShipmentCount() {
        return $this->total_shipments;
    }

}
