<?php

namespace Knowband\Marketplace\Model\ResourceModel\Statusaction;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'vss_mp_statusaction_collection';
    protected $_eventObject = 'marketplace_statusaction_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Knowband\Marketplace\Model\Statusaction', 'Knowband\Marketplace\Model\ResourceModel\Statusaction');
    }

}
