<?php

namespace Knowband\Marketplace\Model\ResourceModel\Transactions;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'row_id';
    protected $_eventPrefix = 'vss_mp_transactions_collection';
    protected $_eventObject = 'marketplace_transactions_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Knowband\Marketplace\Model\Transactions', 'Knowband\Marketplace\Model\ResourceModel\Transactions');
    }

}
