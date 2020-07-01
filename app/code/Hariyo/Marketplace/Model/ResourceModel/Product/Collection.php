<?php

namespace Hariyo\Marketplace\Model\ResourceModel\Product;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'seller_product_id';
    protected $_eventPrefix = 'hariyo_product_to_seller_collection';
    protected $_eventObject = 'marketplace_product_to_seller_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Hariyo\Marketplace\Model\Product', 
        'Hariyo\Marketplace\Model\ResourceModel\Product');
    }

}
