<?php

namespace Hariyo\Marketplace\Model\ResourceModel;

class Settings extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    public function __construct(\Magento\Framework\Model\ResourceModel\Db\Context $context)
    {
        parent::__construct($context);
    }

    protected function _construct()
    {
        $this->_init('hariyo_seller_settings', 'setting_id');
    }

}
