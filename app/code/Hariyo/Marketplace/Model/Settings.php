<?php

namespace Hariyo\Marketplace\Model;

class Settings extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'hariyo_seller_settings';
    protected $_cacheTag = 'hariyo_seller_settings';
    protected $_eventPrefix = 'hariyo_seller_settings';

    protected function _construct()
    {
        $this->_init('Hariyo\Marketplace\Model\ResourceModel\Settings');
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
