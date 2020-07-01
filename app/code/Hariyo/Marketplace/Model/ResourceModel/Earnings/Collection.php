<?php

namespace Hariyo\Marketplace\Model\ResourceModel\Earnings;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'row_id';
    protected $_eventPrefix = 'hariyo_earnings_collection';
    protected $_eventObject = 'marketplace_earnings_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Hariyo\Marketplace\Model\Earnings', 
        'Hariyo\Marketplace\Model\ResourceModel\Earnings');
    }

}
