<?php

namespace Knowband\Marketplace\Model;

class Seller extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'vss_mp_seller_entity';
    protected $_cacheTag = 'vss_mp_seller_entity';
    protected $_eventPrefix = 'vss_mp_seller_entity';

    protected function _construct()
    {
        $this->_init('Knowband\Marketplace\Model\ResourceModel\Seller');
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
