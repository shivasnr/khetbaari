<?php

namespace Knowband\Marketplace\Model\ResourceModel\Reason;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'reason_id';
    protected $_eventPrefix = 'vss_mp_reason_collection';
    protected $_eventObject = 'marketplace_reason_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Knowband\Marketplace\Model\Reason', 'Knowband\Marketplace\Model\ResourceModel\Reason');
    }

}
