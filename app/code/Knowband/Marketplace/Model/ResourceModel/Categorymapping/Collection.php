<?php

namespace Knowband\Marketplace\Model\ResourceModel\Categorymapping;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'row_id';
    protected $_eventPrefix = 'vss_mp_categorymapping_collection';
    protected $_eventObject = 'marketplace_categorymapping_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Knowband\Marketplace\Model\Settings', 'Knowband\Marketplace\Model\ResourceModel\Categorymapping');
    }

}
