<?php

namespace Knowband\Marketplace\Model;

class Shipments extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'vss_mp_seller_shipments';
    protected $_cacheTag = 'vss_mp_seller_shipments';
    protected $_eventPrefix = 'vss_mp_seller_shipments';

    protected function _construct()
    {
        $this->_init('Knowband\Marketplace\Model\ResourceModel\Shipments');
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
