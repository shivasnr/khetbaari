<?php

namespace Knowband\Marketplace\Model\ResourceModel\Emailtemplates;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'template_id';
    protected $_eventPrefix = 'vss_mp_emailtemplates_collection';
    protected $_eventObject = 'marketplace_emailtemplates_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Knowband\Marketplace\Model\Emailtemplates', 'Knowband\Marketplace\Model\ResourceModel\Emailtemplates');
    }

}
