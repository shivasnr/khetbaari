<?php

namespace Knowband\Marketplace\Model\ResourceModel\Settings;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'setting_id';
    protected $_eventPrefix = 'vss_mp_settings_collection';
    protected $_eventObject = 'marketplace_settings_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Knowband\Marketplace\Model\Settings', 'Knowband\Marketplace\Model\ResourceModel\Settings');
    }

}
