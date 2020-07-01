<?php

namespace Knowband\Marketplace\Model\ResourceModel\Seller;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'seller_entity_id';
    protected $_eventPrefix = 'vss_mp_seller_entity_collection';
    protected $_eventObject = 'marketplace_seller_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Knowband\Marketplace\Model\Seller', 'Knowband\Marketplace\Model\ResourceModel\Seller');
    }
    
    protected function _initSelect() {
        $this->addFilterToMap("contact_number", "seller.contact_number");
        parent::_initSelect();
    }
}
