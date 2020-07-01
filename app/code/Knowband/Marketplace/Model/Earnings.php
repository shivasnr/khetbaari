<?php

namespace Knowband\Marketplace\Model;

class Earnings extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'vss_mp_seller_earnings';
    protected $_cacheTag = 'vss_mp_seller_earnings';
    protected $_eventPrefix = 'vss_mp_seller_earnings';

    protected function _construct()
    {
        $this->_init('Knowband\Marketplace\Model\ResourceModel\Earnings');
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
