<?php

namespace Hariyo\Marketplace\Model;

class Orderitem extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'hariyo_seller_item_orders';
    protected $_cacheTag = 'hariyo_seller_item_orders';
    protected $_eventPrefix = 'hariyo_seller_item_orders';

    protected function _construct()
    {
        $this->_init('Hariyo\Marketplace\Model\ResourceModel\Orderitem');
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
