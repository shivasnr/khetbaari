<?php

namespace Hariyo\Marketplace\Model;

class Earnings extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'hariyo_seller_earnings';
    protected $_cacheTag = 'hariyo_seller_earnings';
    protected $_eventPrefix = 'hariyo_seller_earnings';

    protected function _construct()
    {
        $this->_init('Hariyo\Marketplace\Model\ResourceModel\Earnings');
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
