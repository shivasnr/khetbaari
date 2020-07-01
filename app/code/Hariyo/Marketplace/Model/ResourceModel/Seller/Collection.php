<?php

namespace Hariyo\Marketplace\Model\ResourceModel\Seller;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'seller_entity_id';
    protected $_eventPrefix = 'hariyo_seller_entity_collection';
    protected $_eventObject = 'marketplace_seller_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Hariyo\Marketplace\Model\Seller', 'Hariyo\Marketplace\Model\ResourceModel\Seller');
    }
    
    protected function _initSelect() {
        $this->addFilterToMap("contact_number", "seller.contact_number");
        parent::_initSelect();
    }
}
