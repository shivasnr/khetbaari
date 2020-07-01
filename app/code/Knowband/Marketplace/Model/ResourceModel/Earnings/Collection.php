<?php

namespace Knowband\Marketplace\Model\ResourceModel\Earnings;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'row_id';
    protected $_eventPrefix = 'vss_mp_earnings_collection';
    protected $_eventObject = 'marketplace_earnings_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Knowband\Marketplace\Model\Earnings', 'Knowband\Marketplace\Model\ResourceModel\Earnings');
    }

}
