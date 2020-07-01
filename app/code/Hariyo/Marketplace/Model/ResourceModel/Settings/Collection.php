<?php

namespace Hariyo\Marketplace\Model\ResourceModel\Settings;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'setting_id';
    protected $_eventPrefix = 'hariyo_settings_collection';
    protected $_eventObject = 'marketplace_settings_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Hariyo\Marketplace\Model\Settings', 'Hariyo\Marketplace\Model\ResourceModel\Settings');
    }

}
