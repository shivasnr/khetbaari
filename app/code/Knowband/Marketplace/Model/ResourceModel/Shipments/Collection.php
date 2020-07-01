<?php

namespace Knowband\Marketplace\Model\ResourceModel\Shipments;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'seller_shipment_id';
    protected $_eventPrefix = 'vss_mp_shipments_collection';
    protected $_eventObject = 'marketplace_shipments_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Knowband\Marketplace\Model\Shipments', 'Knowband\Marketplace\Model\ResourceModel\Shipments');
    }

}
