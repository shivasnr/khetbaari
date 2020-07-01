<?php

namespace Knowband\Marketplace\Model\ResourceModel\Product;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'seller_product_id';
    protected $_eventPrefix = 'vss_mp_product_to_seller_collection';
    protected $_eventObject = 'marketplace_product_to_seller_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Knowband\Marketplace\Model\Product', 'Knowband\Marketplace\Model\ResourceModel\Product');
    }

}
