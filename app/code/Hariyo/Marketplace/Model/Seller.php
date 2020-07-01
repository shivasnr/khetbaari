<?php

namespace Hariyo\Marketplace\Model;

class Seller extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'hariyo_seller_entity';
    protected $_cacheTag = 'hariyo_seller_entity';
    protected $_eventPrefix = 'hariyo_seller_entity';

    protected function _construct()
    {
        $this->_init('Hariyo\Marketplace\Model\ResourceModel\Seller');
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
