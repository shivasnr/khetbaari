<?php

namespace Hariyo\Marketplace\Model\ResourceModel\Orderitem;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'row_id';
    protected $_eventPrefix = 'hariyo_orderitem_collection';
    protected $_eventObject = 'marketplace_orderitem_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Hariyo\Marketplace\Model\Orderitem', 
        'Hariyo\Marketplace\Model\ResourceModel\Orderitem');
    }

}
