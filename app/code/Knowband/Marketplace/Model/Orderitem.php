<?php

namespace Knowband\Marketplace\Model;

class Orderitem extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'vss_mp_order_item_category';
    protected $_cacheTag = 'vss_mp_order_item_category';
    protected $_eventPrefix = 'vss_mp_order_item_category';

    protected function _construct()
    {
        $this->_init('Knowband\Marketplace\Model\ResourceModel\Orderitem');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        $values = [];
        return $values;
    }
}
