<?php

namespace Knowband\Marketplace\Model\ResourceModel;

class Transactions extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    public function __construct(\Magento\Framework\Model\ResourceModel\Db\Context $context)
    {
        parent::__construct($context);
    }

    protected function _construct()
    {
        $this->_init('vss_mp_seller_transactions', 'row_id');
    }

}
